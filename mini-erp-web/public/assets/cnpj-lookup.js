(function () {
    const configurations = {
        'btn-buscar-cnpj': {
            document: 'company_cnpj',
            fields: { company_razao: 'legal_name', company_apelido: 'trade_name', company_municipio: 'city', company_cep: 'postal_code', company_uf: 'state', company_logradouro: 'street', company_numero: 'number', company_complemento: 'complement', company_bairro: 'district', company_telefone: 'phone_1', company_codigo_ibge: 'city_ibge_code' }
        },
        'btn-buscar-cnpj-plataforma': {
            document: 'platform_cnpj',
            fields: { platform_razao_social: 'legal_name', platform_nome_fantasia: 'trade_name' }
        },
        'btn-buscar-cnpj-cliente': {
            document: 'cliente_cpf_cnpj',
            fields: {
                cliente_nome: 'legal_name', cliente_nome_fantasia: 'trade_name', cliente_cep: 'postal_code',
                cliente_uf: 'state', cliente_cidade: 'city', cliente_numero: 'number', cliente_complemento: 'complement',
                cliente_bairro: 'district', cliente_telefone: 'phone_1', cliente_codigo_ibge: 'city_ibge_code', cliente_logradouro: 'street'
            }
        },
        'btn-buscar-cnpj-fornecedor': {
            document: 'fornecedor_cpf_cnpj',
            fields: {
                fornecedor_nome: 'legal_name', fornecedor_nome_fantasia: 'trade_name', fornecedor_telefone: 'phone_1',
                fornecedor_cep: 'postal_code', fornecedor_uf: 'state', fornecedor_municipio: 'city', fornecedor_cidade: 'city',
                fornecedor_numero: 'number', fornecedor_complemento: 'complement', fornecedor_bairro: 'district', fornecedor_logradouro: 'street'
            }
        },
        'btn-buscar-cnpj-transportadora': {
            document: 'transportadora_cpf_cnpj',
            fields: {
                transportadora_nome: 'legal_name', transportadora_nome_fantasia: 'trade_name', transportadora_telefone: 'phone_1',
                transportadora_cep: 'postal_code', transportadora_uf: 'state', transportadora_municipio: 'city', transportadora_cidade: 'city',
                transportadora_numero: 'number', transportadora_complemento: 'complement', transportadora_bairro: 'district', transportadora_logradouro: 'street'
            }
        },
        'btn-buscar-cnpj-estabelecimento': {
            document: 'establishment_tax_id',
            fields: {
                establishment_legal_name: 'legal_name', establishment_trade_name: 'trade_name', establishment_postal_code: 'postal_code',
                establishment_state: 'state', establishment_city_name: 'city', establishment_number: 'number',
                establishment_complement: 'complement', establishment_district: 'district', establishment_phone: 'phone_1',
                establishment_email: 'email', establishment_city_ibge_code: 'city_ibge_code', establishment_cnae: 'main_cnae', establishment_street: 'street'
            }
        }
    };

    function valueFrom(data, source) {
        if (typeof source === 'function') return source(data);
        if (Array.isArray(source)) {
            for (const key of source) if (data[key]) return data[key];
            return '';
        }
        return data[source] || '';
    }

    function applyValue(field, consultedValue) {
        const value = String(consultedValue || '').trim();
        if (!field || !value) return;
        const container = field.closest('.field-block') || field.closest('label') || field.parentElement;
        const previous = container.querySelector('.cnpj-use-consulted');
        if (previous) previous.remove();
        if (!String(field.value || '').trim()) {
            field.value = value;
            return;
        }
        if (String(field.value).trim().toLocaleLowerCase('pt-BR') === value.toLocaleLowerCase('pt-BR')) return;
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'link-button cnpj-use-consulted';
        action.textContent = 'Usar dado consultado: ' + value;
        action.addEventListener('click', function () { field.value = value; action.remove(); });
        container.appendChild(action);
    }

    Object.entries(configurations).forEach(function ([buttonId, config]) {
        const button = document.getElementById(buttonId);
        const documentField = document.getElementById(config.document);
        if (!button || !documentField) return;
        const personType = buttonId === 'btn-buscar-cnpj-cliente' ? button.closest('form').querySelector('[name="person_type"]') : null;
        const syncPersonType = function () {
            if (!personType) return;
            button.disabled = personType.value !== 'PJ';
            button.title = button.disabled ? 'Disponível para Pessoa Jurídica.' : '';
        };
        if (personType) { personType.addEventListener('change', syncPersonType); syncPersonType(); }
        button.addEventListener('click', async function () {
            const cnpj = documentField.value || '';
            if (!cnpj) return alert('Informe o CNPJ antes de buscar.');
            button.disabled = true;
            button.textContent = 'Buscando...';
            try {
                const endpoint = location.pathname.startsWith('/plataforma/') ? '/plataforma/ajax-cnpj.php' : '/ajax_cnpj.php';
                const response = await fetch(endpoint + '?cnpj=' + encodeURIComponent(cnpj), {credentials: 'same-origin'});
                const payload = await response.json().catch(function () { return {}; });
                if (!response.ok) {
                    const messages = { CNPJ_INVALID: 'O CNPJ informado é inválido.', CNPJ_NOT_FOUND: 'CNPJ não encontrado.', CNPJ_RATE_LIMIT: 'Muitas consultas. Aguarde um minuto.', CNPJ_SERVICE_TIMEOUT: 'A BrasilAPI demorou para responder.', CNPJ_SERVICE_UNAVAILABLE: 'A consulta está indisponível no momento.', AUTH_REQUIRED: 'Sua sessão expirou.' };
                    throw new Error(messages[payload.error] || 'Não foi possível consultar o CNPJ.');
                }
                Object.entries(config.fields).forEach(function ([fieldId, source]) {
                    applyValue(document.getElementById(fieldId), valueFrom(payload.data || {}, source));
                });
                let feedback = button.closest('form').querySelector('.cnpj-lookup-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'message cnpj-lookup-feedback';
                    feedback.setAttribute('role', 'status');
                    button.closest('form').appendChild(feedback);
                }
                const data = payload.data || {};
                const status = data.registration_status_description || data.registration_status || 'Não informada';
                feedback.textContent = '✓ CNPJ localizado — ' + (data.legal_name || '') + ' · Situação: ' + status + '. Dados via BrasilAPI; revise antes de salvar.';
                feedback.classList.toggle('warning', String(status).toUpperCase() !== 'ATIVA');
            } catch (error) {
                alert(error.message);
            } finally {
                button.disabled = false;
                syncPersonType();
                button.textContent = 'Buscar CNPJ';
            }
        });
    });
})();
