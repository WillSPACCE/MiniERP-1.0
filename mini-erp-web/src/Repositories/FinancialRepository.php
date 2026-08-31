<?php
declare(strict_types=1);
namespace MiniErp\Repositories;
use PDO;
use RuntimeException;
use Throwable;

final class FinancialRepository
{
    public function __construct(private PDO $pdo,private int $tenantId){}
    public function syncOrder(array$order,int$actor):void
    {
        $condition=strtolower(trim((string)($order['payment_condition']??'')));if(in_array($condition,['','avista','a vista','à vista'],true))return;
        $type=($order['operation_type']??'EXIT')==='ENTRY'?'PAYABLE':'RECEIVABLE';$due=(string)($order['first_due_date']?:date('Y-m-d',strtotime(((string)$order['operation_date']).' +30 days')));$description=($type==='RECEIVABLE'?'Venda':'Compra').' — pedido #'.(int)$order['id'];
        $sql="INSERT INTO financial_accounts(tenant_id,account_type,source_type,source_id,person_id,description,document_number,issue_date,due_date,original_amount,status,payment_method,created_by) VALUES(?,?,'ORDER',?,?,?,?,?,?,?,'OPEN',?,?) ON DUPLICATE KEY UPDATE person_id=VALUES(person_id),description=VALUES(description),document_number=VALUES(document_number),issue_date=VALUES(issue_date),due_date=VALUES(due_date),original_amount=VALUES(original_amount),payment_method=VALUES(payment_method),status=IF(paid_amount>=VALUES(original_amount),'PAID','OPEN')";
        $this->pdo->prepare($sql)->execute([$this->tenantId,$type,(int)$order['id'],(int)$order['person_id'],$description,(string)($order['internal_code']?:'#'.$order['id']),$order['operation_date'],$due,$order['grand_total'],$order['payment_method'],$actor]);
    }
    public function createPayable(array$data,int$actor):int
    {
        $description=trim((string)($data['description']??''));$amount=$this->money($data['amount']??0);$due=(string)($data['due_date']??'');if($description===''||$amount<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$due))throw new RuntimeException('Informe descrição, valor e vencimento da despesa.');$s=$this->pdo->prepare("INSERT INTO financial_accounts(tenant_id,account_type,source_type,description,document_number,issue_date,due_date,original_amount,status,category,payment_method,notes,created_by) VALUES(?,'PAYABLE','MANUAL',?,?,?,?,?,'OPEN',?,?,?,?)");$s->execute([$this->tenantId,$description,trim((string)($data['document_number']??''))?:null,date('Y-m-d'),$due,$amount,trim((string)($data['category']??''))?:null,trim((string)($data['payment_method']??''))?:null,trim((string)($data['notes']??''))?:null,$actor]);return(int)$this->pdo->lastInsertId();
    }
    public function settle(int$id,float$amount,string$method,string$notes,int$actor):void
    {
        if($id<1||$amount<=0)throw new RuntimeException('Informe uma baixa válida.');$this->transaction(function()use($id,$amount,$method,$notes,$actor){$s=$this->pdo->prepare('SELECT * FROM financial_accounts WHERE id=? AND tenant_id=? FOR UPDATE');$s->execute([$id,$this->tenantId]);$a=$s->fetch(PDO::FETCH_ASSOC);if(!$a)throw new RuntimeException('Conta não encontrada.');$remaining=(float)$a['original_amount']-(float)$a['paid_amount'];if($amount>$remaining+.001)throw new RuntimeException('A baixa é maior que o saldo da conta.');$this->pdo->prepare('INSERT INTO financial_payments(tenant_id,account_id,amount,paid_at,payment_method,notes,created_by) VALUES(?,?,?,NOW(),?,?,?)')->execute([$this->tenantId,$id,$amount,$method?:null,$notes?:null,$actor]);$paid=(float)$a['paid_amount']+$amount;$status=$paid+0.001>=(float)$a['original_amount']?'PAID':'PARTIAL';$this->pdo->prepare('UPDATE financial_accounts SET paid_amount=?,status=? WHERE id=? AND tenant_id=?')->execute([$paid,$status,$id,$this->tenantId]);});
    }
    public function accounts(string$type='',string$status=''):array{$where=['a.tenant_id=?'];$params=[$this->tenantId];if(in_array($type,['RECEIVABLE','PAYABLE'],true)){$where[]='a.account_type=?';$params[]=$type;}if(in_array($status,['OPEN','PARTIAL','PAID','OVERDUE'],true)){if($status==='OVERDUE')$where[]="a.status IN ('OPEN','PARTIAL') AND a.due_date<CURRENT_DATE";else{$where[]='a.status=?';$params[]=$status;}}$sql="SELECT a.*,CASE WHEN a.account_type='RECEIVABLE' THEN c.nome ELSE f.nome END person_name,(a.original_amount-a.paid_amount) balance FROM financial_accounts a LEFT JOIN clientes c ON c.id=a.person_id LEFT JOIN fornecedores f ON f.id=a.person_id WHERE ".implode(' AND ',$where).' ORDER BY a.due_date,a.id DESC';$s=$this->pdo->prepare($sql);$s->execute($params);return$s->fetchAll(PDO::FETCH_ASSOC);}
    public function summary():array{$s=$this->pdo->prepare("SELECT COALESCE(SUM(CASE WHEN account_type='RECEIVABLE' AND status IN ('OPEN','PARTIAL') THEN original_amount-paid_amount ELSE 0 END),0) receivable,COALESCE(SUM(CASE WHEN account_type='PAYABLE' AND status IN ('OPEN','PARTIAL') THEN original_amount-paid_amount ELSE 0 END),0) payable,COALESCE(SUM(CASE WHEN status IN ('OPEN','PARTIAL') AND due_date<CURRENT_DATE THEN original_amount-paid_amount ELSE 0 END),0) overdue FROM financial_accounts WHERE tenant_id=?");$s->execute([$this->tenantId]);$r=$s->fetch(PDO::FETCH_ASSOC)?:[];return['receivable'=>(float)($r['receivable']??0),'payable'=>(float)($r['payable']??0),'overdue'=>(float)($r['overdue']??0)];}
    private function money(mixed$v):float{return max(0,(float)str_replace(',','.',preg_replace('/[^0-9,.-]/','',(string)$v)));}
    private function transaction(callable$fn):mixed{$own=!$this->pdo->inTransaction();if($own)$this->pdo->beginTransaction();try{$r=$fn();if($own)$this->pdo->commit();return$r;}catch(Throwable$e){if($own&&$this->pdo->inTransaction())$this->pdo->rollBack();throw$e;}}
}
