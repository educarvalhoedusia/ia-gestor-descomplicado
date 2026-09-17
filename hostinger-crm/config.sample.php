<?php
// Copie este arquivo para "config.php" (mesma pasta) e preencha com os dados
// reais do seu banco, encontrados em hPanel > Bancos de dados MySQL.
// NUNCA suba o config.php real para um repositório público.

return [
    // hPanel > Bancos de dados MySQL costuma usar "localhost" como host.
    'db_host' => 'localhost',
    'db_name' => 'u000000000_edusia_crm',
    'db_user' => 'u000000000_edusia',
    'db_pass' => 'TROQUE_ESTA_SENHA',

    // Token simples para proteger a API — funciona como uma "senha da sala"
    // que toda a equipe digita para entrar no painel. O valor abaixo já é
    // seguro e pode ser usado como está; troque por outra frase longa sem
    // espaços se preferir escolher a sua própria.
    'api_token' => 'NXUqLwqxjJdOmgfRCMJby5mTrOPl_CfD',

    // Usados pelo cron_followup.php para enviar os e-mails automáticos de
    // follow-up (veja README.md, seção "E-mails automáticos de follow-up").
    'email_from' => 'crm@edusia.com.br',
    'email_from_name' => 'EdusIA — Painel Comercial',
    'painel_url' => 'https://edusia.com.br/crm/',

    // Usada pelo botão "🤖 Sugerir resposta" (assistente de IA que ajuda o
    // vendedor a responder o comprador). Crie sua chave em
    // console.anthropic.com > API Keys. Deixe em branco para desativar o
    // recurso sem quebrar o resto do painel.
    'anthropic_api_key' => '',
    'ai_model' => 'claude-haiku-4-5-20251001',

    // Frase institucional que a IA pode encaixar (com naturalidade, não em
    // toda resposta) nas sugestões geradas. Deixe em branco para não usar.
    'frase_padrao' => 'Na EdusIA, a gente não fica só na teoria — o foco é aplicar IA na prática e gerar resultado rápido.',

    // Usada pela aba "Prospectar Clientes" (busca de empresas no Google
    // Maps). Crie em console.cloud.google.com > APIs e Serviços > Credenciais,
    // com a "Places API" ativada e faturamento configurado (tem cota
    // gratuita mensal). Deixe em branco para desativar a aba sem quebrar o
    // resto do painel.
    'google_maps_api_key' => '',
];
