<?php
declare(strict_types=1);

// EdusIA Painel Comercial — API PHP/MySQL para Hostinger (hospedagem compartilhada)
// Endpoint único: api.php?action=...
// Todas as respostas são JSON. Erros retornam {"error": "..."} com status HTTP != 200.

header('Content-Type: application/json; charset=utf-8');
@set_time_limit(90);

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro fatal no servidor: ' . $e['message'] . ' em ' . basename($e['file']) . ':' . $e['line']]);
    }
});

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php não encontrado. Copie config.sample.php para config.php e preencha os dados do banco.']);
    exit;
}
$config = require $configFile;

function fail(int $status, string $msg): void {
    http_response_code($status);
    echo json_encode(['error' => $msg]);
    exit;
}

function httpGetSimple(string $url, int $timeoutSeconds) {
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'timeout' => $timeoutSeconds,
        'ignore_errors' => true,
    ]]);
    return @file_get_contents($url, false, $context);
}

function httpGetSimpleAuth(string $url, array $headers, int $timeoutSeconds) {
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => implode("\r\n", $headers) . "\r\n",
        'timeout' => $timeoutSeconds,
        'ignore_errors' => true,
    ]]);
    return @file_get_contents($url, false, $context);
}

function httpPostJson(string $url, array $headers, array $body, int $timeoutSeconds) {
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers) . "\r\n",
        'content' => json_encode($body),
        'timeout' => $timeoutSeconds,
        'ignore_errors' => true,
    ]]);
    return @file_get_contents($url, false, $context);
}

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    fail(500, 'Falha ao conectar ao banco de dados: ' . $e->getMessage());
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

function uid(): string {
    return bin2hex(random_bytes(8));
}

function callAnthropic(array $config, string $system, string $userMessage, int $maxTokens = 700): string {
    $apiKey = $config['anthropic_api_key'] ?? '';
    if (!$apiKey) {
        fail(500, 'Chave de IA não configurada. Adicione "anthropic_api_key" no config.php.');
    }
    $model = $config['ai_model'] ?? 'claude-haiku-4-5-20251001';

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 18,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $userMessage]],
        ]),
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        fail(502, 'Falha ao conectar com a IA: ' . $curlError);
    }
    $decoded = json_decode($response, true);
    if ($httpCode !== 200 || !isset($decoded['content'][0]['text'])) {
        $errMsg = $decoded['error']['message'] ?? ('HTTP ' . $httpCode);
        fail(502, 'Falha ao gerar texto com IA: ' . $errMsg);
    }
    return trim($decoded['content'][0]['text']);
}

function mapProspectRow(array $r): array {
    return [
        'id' => $r['id'],
        'nome' => $r['nome'],
        'endereco' => $r['endereco'],
        'cidade' => $r['cidade'],
        'uf' => $r['uf'] ?? '',
        'ramo' => $r['ramo'],
        'telefone' => $r['telefone'],
        'email' => $r['email'],
        'site' => $r['site'],
        'rating' => $r['rating'] !== null ? (float)$r['rating'] : null,
        'fonte' => $r['fonte'] ?? 'google_maps',
        'cnpj' => $r['cnpj'] ?? '',
        'razaoSocial' => $r['razao_social'] ?? '',
        'dataAbertura' => $r['data_abertura'] ?? null,
        'capitalSocial' => isset($r['capital_social']) && $r['capital_social'] !== null ? (float)$r['capital_social'] : null,
        'situacaoCadastral' => $r['situacao_cadastral'] ?? '',
        'porteEmpresa' => $r['porte_empresa'] ?? '',
        'socios' => $r['socios'] ?? '',
        'vendedor' => $r['vendedor'],
        'status' => $r['status'],
        'resposta' => $r['resposta'],
        'mensagemSugerida' => $r['mensagem_sugerida'],
        'contactId' => $r['contact_id'],
        'createdAt' => str_replace(' ', 'T', $r['created_at']),
    ];
}

function casaDosDadosBusca(array $config, array $body): array {
    $token = $config['casa_dos_dados_token'] ?? '';
    if (!$token) {
        fail(500, 'Token da Casa dos Dados não configurado. Adicione "casa_dos_dados_token" no config.php.');
    }
    $response = httpPostJson(
        'https://api.casadosdados.com.br/v5/cnpj/pesquisa',
        ['api-key: ' . $token, 'Content-Type: application/json'],
        $body,
        15
    );
    if ($response === false) {
        fail(502, 'Falha ao consultar a Casa dos Dados.');
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded) || !isset($decoded['cnpjs'])) {
        $msg = $decoded['message'] ?? $decoded['error'] ?? substr($response, 0, 200);
        fail(502, 'Casa dos Dados retornou erro: ' . $msg);
    }
    return $decoded;
}

function casaDosDadosDetalhe(array $config, string $cnpj) {
    $token = $config['casa_dos_dados_token'] ?? '';
    if (!$token) return null;
    $response = httpGetSimpleAuth(
        'https://api.casadosdados.com.br/v4/cnpj/' . urlencode($cnpj),
        ['api-key: ' . $token],
        15
    );
    if ($response === false) return null;
    $decoded = json_decode($response, true);
    return (is_array($decoded) && isset($decoded['cnpj'])) ? $decoded : null;
}

function socioNomes(array $cnpjData): string {
    $nomes = [];
    foreach (($cnpjData['quadro_societario'] ?? []) as $s) {
        if (!empty($s['nome'])) $nomes[] = $s['nome'];
    }
    return implode(', ', array_slice($nomes, 0, 6));
}

function enderecoCompleto(array $cnpjData): string {
    $e = $cnpjData['endereco'] ?? [];
    $partes = array_filter([
        trim(($e['tipo_logradouro'] ?? '') . ' ' . ($e['logradouro'] ?? '')),
        $e['numero'] ?? '',
        $e['bairro'] ?? '',
        $e['municipio'] ?? '',
        $e['uf'] ?? '',
        $e['cep'] ?? '',
    ]);
    return implode(', ', $partes);
}

function situacaoAtual(array $cnpjData): string {
    return $cnpjData['situacao_cadastral']['situacao_atual'] ?? '';
}

function telefonePrincipal(array $cnpjData): string {
    $t = ($cnpjData['contato_telefonico'] ?? [])[0] ?? null;
    if (!$t) return '';
    return $t['completo'] ?? trim(($t['ddd'] ?? '') . ' ' . ($t['numero'] ?? ''));
}

function emailPrincipal(array $cnpjData): string {
    return ($cnpjData['contato_email'] ?? [])[0]['email'] ?? '';
}

function reqStr($v, string $field, bool $required = true): string {
    $s = is_string($v) ? trim($v) : '';
    if ($required && $s === '') fail(422, "Campo obrigatório ausente: $field");
    return $s;
}

// --- login: não exige token, verifica e-mail + senha ---
if ($action === 'login') {
    $email = strtolower(reqStr($input['email'] ?? null, 'email'));
    $senha = reqStr($input['senha'] ?? null, 'senha');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha, $user['password_hash'])) {
        fail(401, 'E-mail ou senha inválidos.');
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare('INSERT INTO access_log (user_id, nome, email, ip, ocorrido_em) VALUES (?, ?, ?, ?, ?)')
        ->execute([$user['id'], $user['nome'], $user['email'], $ip, date('Y-m-d H:i:s')]);

    echo json_encode([
        'token' => $user['api_token'],
        'nome' => $user['nome'],
        'isAdmin' => (bool)$user['is_admin'],
    ]);
    exit;
}

// --- autenticação: token mestre (config.php) OU token de um usuário cadastrado ---
$sentToken = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
$currentUser = null; // null quando autenticado pelo token mestre

if (hash_equals((string)$config['api_token'], (string)$sentToken)) {
    $currentUser = ['id' => null, 'nome' => 'Administrador', 'is_admin' => 1];
} else {
    $stmt = $pdo->prepare('SELECT id, nome, is_admin FROM users WHERE api_token = ?');
    $stmt->execute([$sentToken]);
    $currentUser = $stmt->fetch() ?: null;
}

if (!$currentUser) {
    fail(401, 'Sessão inválida. Faça login novamente.');
}

function requireAdmin($currentUser): void {
    if (empty($currentUser['is_admin'])) {
        fail(403, 'Apenas administradores podem fazer isso.');
    }
}

$isAdmin = !empty($currentUser['is_admin']);
$meuNome = $currentUser['nome'] ?? '';

try {
switch ($action) {

    case 'list': {
        // Acesso total (todos os contatos) é só para administradores; cada
        // vendedor só recebe os próprios leads (contacts.vendedor = seu nome).
        if ($isAdmin) {
            $contacts = $pdo->query('SELECT * FROM contacts ORDER BY created_at DESC')->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT * FROM contacts WHERE vendedor = ? ORDER BY created_at DESC');
            $stmt->execute([$meuNome]);
            $contacts = $stmt->fetchAll();
        }
        $interactions = $pdo->query('SELECT * FROM interactions ORDER BY data_hora DESC')->fetchAll();

        $byContact = [];
        foreach ($interactions as $it) {
            $byContact[$it['contact_id']][] = [
                'id' => $it['id'],
                'dataHora' => str_replace(' ', 'T', $it['data_hora']),
                'canal' => $it['canal'],
                'resumo' => $it['resumo'],
                'temperatura' => $it['temperatura'],
                'proximaAcao' => $it['proxima_acao'],
                'dataFollowUp' => $it['data_followup'],
                'pilar' => $it['pilar'],
                'produto' => $it['produto'],
                'valor' => (float)$it['valor'],
                'estagio' => $it['estagio'],
                'vendedorRegistro' => $it['vendedor_registro'],
                'createdAt' => str_replace(' ', 'T', $it['created_at']),
            ];
        }

        // $byContact pode conter interações de contatos de outros vendedores
        // (a query acima busca todas), mas só as dos contatos abaixo (já
        // filtrados) chegam ao $out — nada de outros vendedores vaza.
        $out = [];
        foreach ($contacts as $c) {
            $out[] = [
                'id' => $c['id'],
                'nome' => $c['nome'],
                'empresa' => $c['empresa'],
                'cargo' => $c['cargo'],
                'telefone' => $c['telefone'],
                'email' => $c['email'],
                'origem' => $c['origem'],
                'disc' => $c['disc'],
                'vendedor' => $c['vendedor'],
                'tags' => $c['tags'] ? json_decode($c['tags'], true) : [],
                'createdAt' => str_replace(' ', 'T', $c['created_at']),
                'interactions' => $byContact[$c['id']] ?? [],
            ];
        }
        echo json_encode($out);
        break;
    }

    case 'save_contact': {
        $id = isset($input['id']) && $input['id'] !== '' ? (string)$input['id'] : uid();
        $nome = reqStr($input['nome'] ?? null, 'nome');
        $telefone = reqStr($input['telefone'] ?? null, 'telefone');
        $vendedor = reqStr($input['vendedor'] ?? null, 'vendedor');
        $origem = reqStr($input['origem'] ?? null, 'origem');

        $exists = $pdo->prepare('SELECT id, created_at FROM contacts WHERE id = ?');
        $exists->execute([$id]);
        $row = $exists->fetch();
        $createdAt = $row ? $row['created_at'] : ($input['createdAt'] ?? date('Y-m-d H:i:s'));

        $tagsJson = json_encode(array_values($input['tags'] ?? []));

        $stmt = $pdo->prepare('
            REPLACE INTO contacts (id, nome, empresa, cargo, telefone, email, origem, disc, vendedor, tags, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $id, $nome,
            reqStr($input['empresa'] ?? null, 'empresa', false),
            reqStr($input['cargo'] ?? null, 'cargo', false),
            $telefone,
            reqStr($input['email'] ?? null, 'email', false),
            $origem,
            reqStr($input['disc'] ?? null, 'disc', false),
            $vendedor,
            $tagsJson,
            $createdAt,
        ]);
        echo json_encode(['id' => $id]);
        break;
    }

    case 'delete_contact': {
        $id = reqStr($input['id'] ?? null, 'id');
        $pdo->prepare('DELETE FROM contacts WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'add_interaction': {
        $contactId = reqStr($input['contactId'] ?? null, 'contactId');
        $chk = $pdo->prepare('SELECT id FROM contacts WHERE id = ?');
        $chk->execute([$contactId]);
        if (!$chk->fetch()) fail(404, 'Contato não encontrado.');

        $id = uid();
        $stmt = $pdo->prepare('
            INSERT INTO interactions
              (id, contact_id, data_hora, canal, resumo, temperatura, proxima_acao, data_followup, pilar, produto, valor, estagio, vendedor_registro, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $id, $contactId,
            str_replace('T', ' ', reqStr($input['dataHora'] ?? null, 'dataHora')),
            reqStr($input['canal'] ?? null, 'canal'),
            reqStr($input['resumo'] ?? null, 'resumo'),
            reqStr($input['temperatura'] ?? null, 'temperatura'),
            reqStr($input['proximaAcao'] ?? null, 'proximaAcao', false),
            (isset($input['dataFollowUp']) && $input['dataFollowUp'] !== '') ? $input['dataFollowUp'] : null,
            reqStr($input['pilar'] ?? null, 'pilar'),
            reqStr($input['produto'] ?? null, 'produto'),
            (float)($input['valor'] ?? 0),
            reqStr($input['estagio'] ?? null, 'estagio'),
            reqStr($input['vendedorRegistro'] ?? null, 'vendedorRegistro', false),
            date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['id' => $id]);
        break;
    }

    case 'delete_interaction': {
        $id = reqStr($input['id'] ?? null, 'id');
        $pdo->prepare('DELETE FROM interactions WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'ranking': {
        // Ranking geral (negócios fechados-ganho) visível para toda a
        // equipe. O valor em R$ só aparece para administradores e para o
        // próprio vendedor na sua própria linha — os demais veem só a
        // posição e a quantidade de negócios ganhos.
        $rows = $pdo->query("
            SELECT c.vendedor AS vendedor,
                   SUM(CASE WHEN i.estagio = 'ganho' THEN 1 ELSE 0 END) AS ganhos,
                   SUM(CASE WHEN i.estagio = 'ganho' THEN i.valor ELSE 0 END) AS valor
            FROM contacts c
            LEFT JOIN interactions i ON i.contact_id = c.id
            GROUP BY c.vendedor
            ORDER BY ganhos DESC, valor DESC
        ")->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $ehMinhaLinha = trim($r['vendedor']) === trim($meuNome);
            $out[] = [
                'vendedor' => $r['vendedor'],
                'ganhos' => (int)$r['ganhos'],
                'valor' => ($isAdmin || $ehMinhaLinha) ? (float)$r['valor'] : null,
                'ehMinhaLinha' => $ehMinhaLinha,
            ];
        }
        echo json_encode($out);
        break;
    }

    case 'list_sellers': {
        $rows = $pdo->query('SELECT nome FROM sellers ORDER BY nome')->fetchAll();
        echo json_encode(array_map(fn($r) => $r['nome'], $rows));
        break;
    }

    case 'add_seller': {
        $nome = reqStr($input['nome'] ?? null, 'nome');
        $pdo->prepare('INSERT IGNORE INTO sellers (nome) VALUES (?)')->execute([$nome]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'change_password': {
        if (empty($currentUser['id'])) {
            fail(400, 'Entre com seu e-mail e senha (não com o token mestre) para trocar a senha.');
        }
        $novaSenha = reqStr($input['novaSenha'] ?? null, 'novaSenha');
        if (strlen($novaSenha) < 6) {
            fail(422, 'A nova senha precisa ter pelo menos 6 caracteres.');
        }
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $currentUser['id']]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'list_users': {
        requireAdmin($currentUser);
        $rows = $pdo->query('SELECT id, nome, email, is_admin, created_at FROM users ORDER BY nome')->fetchAll();
        echo json_encode(array_map(function($r) {
            return [
                'id' => (int)$r['id'],
                'nome' => $r['nome'],
                'email' => $r['email'],
                'isAdmin' => (bool)$r['is_admin'],
                'createdAt' => str_replace(' ', 'T', $r['created_at']),
            ];
        }, $rows));
        break;
    }

    case 'add_user': {
        requireAdmin($currentUser);
        $nome = reqStr($input['nome'] ?? null, 'nome');
        $email = strtolower(reqStr($input['email'] ?? null, 'email'));
        $senha = reqStr($input['senha'] ?? null, 'senha');
        if (strlen($senha) < 6) {
            fail(422, 'A senha precisa ter pelo menos 6 caracteres.');
        }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(24));
        try {
            $pdo->prepare('
                INSERT INTO users (nome, email, password_hash, api_token, is_admin, created_at)
                VALUES (?, ?, ?, ?, 0, ?)
            ')->execute([$nome, $email, $hash, $token, date('Y-m-d H:i:s')]);
        } catch (PDOException $e) {
            fail(409, 'Já existe uma conta com esse e-mail.');
        }
        $pdo->prepare('INSERT IGNORE INTO sellers (nome) VALUES (?)')->execute([$nome]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'list_access_log': {
        requireAdmin($currentUser);
        $rows = $pdo->query('SELECT nome, email, ip, ocorrido_em FROM access_log ORDER BY ocorrido_em DESC LIMIT 300')->fetchAll();
        echo json_encode(array_map(function($r) {
            return [
                'nome' => $r['nome'],
                'email' => $r['email'],
                'ip' => $r['ip'],
                'ocorridoEm' => str_replace(' ', 'T', $r['ocorrido_em']),
            ];
        }, $rows));
        break;
    }

    case 'suggest_reply': {
        $contactId = reqStr($input['contactId'] ?? null, 'contactId');
        $respostaComprador = reqStr($input['respostaComprador'] ?? null, 'respostaComprador');

        $stmt = $pdo->prepare('SELECT * FROM contacts WHERE id = ?');
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch();
        if (!$contact) fail(404, 'Contato não encontrado.');
        if (!$isAdmin && trim($contact['vendedor']) !== trim($meuNome)) {
            fail(403, 'Você só pode gerar sugestões para os próprios leads.');
        }

        $intStmt = $pdo->prepare('SELECT * FROM interactions WHERE contact_id = ? ORDER BY data_hora DESC LIMIT 5');
        $intStmt->execute([$contactId]);
        $interactions = array_reverse($intStmt->fetchAll());

        $historico = '';
        foreach ($interactions as $it) {
            $historico .= "- [{$it['data_hora']}] Canal: {$it['canal']}. Resumo: {$it['resumo']}. "
                . "Estágio: {$it['estagio']}. Produto: {$it['produto']} ({$it['pilar']}). "
                . "Valor da proposta: R$ " . number_format((float)$it['valor'], 2, ',', '.') . ".\n";
        }
        if ($historico === '') {
            $historico = "(nenhuma conversa registrada ainda)\n";
        }

        $empresaNome = $config['email_from_name'] ?? 'a empresa';
        $contexto = "Lead: {$contact['nome']}"
            . ($contact['empresa'] ? " ({$contact['empresa']})" : '')
            . ($contact['cargo'] ? ", cargo: {$contact['cargo']}" : '')
            . ($contact['disc'] ? ". Perfil DISC: {$contact['disc']}" : '') . "\n"
            . "Origem do lead: {$contact['origem']}\n\n"
            . "Histórico de conversas recentes:\n{$historico}\n"
            . "O comprador acabou de responder o seguinte:\n\"{$respostaComprador}\"\n\n"
            . "Escreva a melhor resposta possível para convencê-lo a avançar na negociação.";

        $fraseEmpresa = $config['frase_padrao'] ?? '';
        $system = "Você é um assistente de vendas experiente ajudando um vendedor de {$empresaNome}. "
            . "Sua tarefa é sugerir uma resposta persuasiva, informal e direta (não robótica, nada de "
            . "formalidade exagerada) para o vendedor enviar ao lead, considerando o perfil DISC dele "
            . "quando disponível (D=direto e objetivo, I=entusiasmado e social, S=paciente e "
            . "tranquilizador, C=lógico e detalhado). Escreva como alguém que manda mensagem de "
            . "verdade no WhatsApp: frases curtas, sem rodeio, pode usar contrações e uma linguagem "
            . "mais solta, sem perder o profissionalismo nem parecer geração automática. Vá direto ao "
            . "ponto da objeção do comprador nas primeiras linhas, sem introdução genérica. "
            . "Responda em português do Brasil, pronto para o vendedor copiar e enviar como está "
            . "(sem aspas ao redor do texto). Não invente promessas, preços, prazos ou condições que "
            . "não estejam no histórico fornecido — se precisar de uma informação que não tem, deixe "
            . "um espaço claro tipo [confirmar prazo] em vez de inventar."
            . ($fraseEmpresa ? " Quando fizer sentido no contexto (não em toda resposta, só quando "
                . "reforçar o argumento), pode encaixar de forma natural — nunca robótica — uma "
                . "variação desta frase institucional da empresa: \"{$fraseEmpresa}\"." : '');

        $sugestao = callAnthropic($config, $system, $contexto, 700);
        echo json_encode(['sugestao' => $sugestao]);
        break;
    }

    case 'prospect_search': {
        $cidade = reqStr($input['cidade'] ?? null, 'cidade');
        $ramo = reqStr($input['ramo'] ?? null, 'ramo');

        $apiKey = $config['google_maps_code'] ?? '';
        if (!$apiKey) {
            fail(500, 'Chave do Google Maps não configurada. Adicione "google_maps_code" no config.php.');
        }

        $query = $ramo . ' em ' . $cidade;
        $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . urlencode($query)
            . '&key=' . urlencode($apiKey) . '&language=pt-BR&region=br';

        $response = httpGetSimple($url, 12);
        if ($response === false) {
            fail(502, 'Falha ao consultar o Google Maps.');
        }
        $decoded = json_decode($response, true);
        $status = $decoded['status'] ?? '';
        if ($status !== 'OK' && $status !== 'ZERO_RESULTS') {
            fail(502, 'Google Maps retornou erro: ' . $status . ' — ' . ($decoded['error_message'] ?? ''));
        }
        $results = array_slice($decoded['results'] ?? [], 0, 12);

        $novos = 0;
        $out = [];
        foreach ($results as $r) {
            $placeId = $r['place_id'] ?? null;
            if (!$placeId) continue;

            $exists = $pdo->prepare('SELECT * FROM prospects WHERE place_id = ?');
            $exists->execute([$placeId]);
            $row = $exists->fetch();

            if (!$row) {
                // Busca telefone/site só para lugares novos, e só nos primeiros da lista,
                // para não estourar o tempo de execução do servidor com muitas chamadas em série.
                $telefone = '';
                $site = '';
                if ($novos < 5) {
                    $detUrl = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . urlencode($placeId)
                        . '&fields=formatted_phone_number,website&key=' . urlencode($apiKey) . '&language=pt-BR';
                    $detResp = httpGetSimple($detUrl, 5);
                    if ($detResp !== false) {
                        $det = json_decode($detResp, true);
                        $telefone = $det['result']['formatted_phone_number'] ?? '';
                        $site = $det['result']['website'] ?? '';
                    }
                }

                $id = uid();
                $createdAt = date('Y-m-d H:i:s');
                $pdo->prepare('
                    INSERT INTO prospects (id, place_id, nome, endereco, cidade, ramo, telefone, site, rating, vendedor, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ')->execute([
                    $id, $placeId, $r['name'] ?? '(sem nome)', $r['formatted_address'] ?? '', $cidade, $ramo,
                    $telefone, $site, $r['rating'] ?? null, $meuNome, 'novo', $createdAt,
                ]);
                $novos++;
                $row = [
                    'id' => $id, 'nome' => $r['name'] ?? '(sem nome)', 'endereco' => $r['formatted_address'] ?? '',
                    'cidade' => $cidade, 'ramo' => $ramo, 'telefone' => $telefone, 'email' => '', 'site' => $site,
                    'rating' => $r['rating'] ?? null, 'vendedor' => $meuNome, 'status' => 'novo', 'resposta' => null,
                    'mensagem_sugerida' => null, 'contact_id' => null, 'created_at' => $createdAt,
                ];
            }
            $out[] = $row;
        }

        echo json_encode(['novos' => $novos, 'total' => count($out), 'prospects' => array_map('mapProspectRow', $out)]);
        break;
    }

    case 'prospect_search_cnpj': {
        $cidade = reqStr($input['cidade'] ?? null, 'cidade');
        $uf = strtoupper(reqStr($input['uf'] ?? null, 'uf'));
        $ramo = reqStr($input['ramo'] ?? null, 'ramo', false);
        $cnaeInput = reqStr($input['cnae'] ?? null, 'cnae', false);
        $cnae = preg_replace('/\D+/', '', $cnaeInput);
        if (!$ramo && !$cnae) fail(422, 'Informe o ramo de atuação ou o código CNAE.');

        $body = [
            'municipio' => [$cidade],
            'uf' => [$uf],
            'situacao_cadastral' => ['ATIVA'],
            'limite' => 12,
        ];
        if ($cnae) {
            // A API espera o CNAE sem pontuação (ex.: "6920601").
            $body['codigo_atividade_principal'] = [$cnae];
        } else {
            $body['busca_textual'] = [[
                'texto' => [$ramo],
                'razao_social' => true,
                'nome_fantasia' => true,
                'tipo_busca' => 'radical',
            ]];
        }

        $data = casaDosDadosBusca($config, $body);
        $ramo = $ramo ?: ('CNAE ' . $cnaeInput);

        $novos = 0;
        $out = [];
        foreach (($data['cnpjs'] ?? []) as $r) {
            $cnpj = $r['cnpj'] ?? null;
            if (!$cnpj) continue;
            $placeId = 'cd_' . $cnpj;

            $exists = $pdo->prepare('SELECT * FROM prospects WHERE place_id = ?');
            $exists->execute([$placeId]);
            $row = $exists->fetch();

            if (!$row) {
                // Busca dados completos (endereço, sócios, telefone, e-mail) só para os
                // primeiros novos, para não estourar o tempo de execução do servidor.
                $det = $novos < 5 ? casaDosDadosDetalhe($config, $cnpj) : null;
                $full = $det ?: $r;

                $id = uid();
                $createdAt = date('Y-m-d H:i:s');
                $nome = $full['nome_fantasia'] ?: $full['razao_social'] ?? '(sem nome)';
                $enderecoUf = $full['endereco']['uf'] ?? $uf;
                $enderecoCidade = $full['endereco']['municipio'] ?? $cidade;
                $socios = $det ? socioNomes($det) : '';
                $endereco = $det ? enderecoCompleto($det) : '';
                $porte = $full['porte_empresa']['descricao'] ?? '';
                $situacao = situacaoAtual($full);
                $telefone = $det ? telefonePrincipal($det) : '';
                $email = $det ? emailPrincipal($det) : '';

                $pdo->prepare('
                    INSERT INTO prospects (
                        id, place_id, nome, endereco, cidade, uf, ramo, telefone, email, site, rating, fonte,
                        cnpj, razao_social, data_abertura, capital_social, situacao_cadastral, porte_empresa, socios,
                        vendedor, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ')->execute([
                    $id, $placeId, $nome, $endereco, $enderecoCidade, $enderecoUf, $ramo, $telefone, $email, '', null,
                    'casa_dos_dados', $cnpj, $full['razao_social'] ?? '', $det['data_abertura'] ?? null,
                    $det['capital_social'] ?? null, $situacao, $porte, $socios, $meuNome, 'novo', $createdAt,
                ]);
                $novos++;
                $row = [
                    'id' => $id, 'nome' => $nome, 'endereco' => $endereco, 'cidade' => $enderecoCidade,
                    'uf' => $enderecoUf, 'ramo' => $ramo, 'telefone' => $telefone, 'email' => $email, 'site' => '',
                    'rating' => null, 'fonte' => 'casa_dos_dados', 'cnpj' => $cnpj,
                    'razao_social' => $full['razao_social'] ?? '', 'data_abertura' => $det['data_abertura'] ?? null,
                    'capital_social' => $det['capital_social'] ?? null, 'situacao_cadastral' => $situacao,
                    'porte_empresa' => $porte, 'socios' => $socios, 'vendedor' => $meuNome, 'status' => 'novo',
                    'resposta' => null, 'mensagem_sugerida' => null, 'contact_id' => null, 'created_at' => $createdAt,
                ];
            }
            $out[] = $row;
        }

        echo json_encode(['novos' => $novos, 'total' => count($out), 'prospects' => array_map('mapProspectRow', $out)]);
        break;
    }

    case 'prospect_enrich_cnpj': {
        $id = reqStr($input['id'] ?? null, 'id');

        $stmt = $pdo->prepare('SELECT * FROM prospects WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) fail(404, 'Prospect não encontrado.');
        if (!$isAdmin && trim($p['vendedor']) !== trim($meuNome)) {
            fail(403, 'Você só pode enriquecer os próprios prospects.');
        }

        $municipio = [$p['cidade']];
        $ufFiltro = $p['uf'] ?? '';

        $body = [
            'busca_textual' => [[
                'texto' => [$p['nome']],
                'razao_social' => true,
                'nome_fantasia' => true,
                'tipo_busca' => 'radical',
            ]],
            'municipio' => $municipio,
            'limite' => 1,
        ];
        if ($ufFiltro) $body['uf'] = [$ufFiltro];

        $data = casaDosDadosBusca($config, $body);
        $achado = ($data['cnpjs'] ?? [])[0] ?? null;
        if (!$achado || empty($achado['cnpj'])) {
            echo json_encode(['ok' => false, 'motivo' => 'Nenhuma empresa correspondente encontrada na Casa dos Dados.']);
            break;
        }

        $det = casaDosDadosDetalhe($config, $achado['cnpj']);
        if (!$det) {
            echo json_encode(['ok' => false, 'motivo' => 'Empresa encontrada, mas não foi possível obter os dados completos.']);
            break;
        }

        $socios = socioNomes($det);
        $endereco = enderecoCompleto($det);
        $porte = $det['porte_empresa']['descricao'] ?? '';
        $situacao = situacaoAtual($det);
        $ufResp = $det['endereco']['uf'] ?? $ufFiltro;
        $telefone = telefonePrincipal($det);
        $email = emailPrincipal($det);

        $pdo->prepare('
            UPDATE prospects SET
                cnpj = ?, razao_social = ?, data_abertura = ?, capital_social = ?, situacao_cadastral = ?,
                porte_empresa = ?, socios = ?, endereco = ?, uf = ?,
                telefone = CASE WHEN telefone = \'\' THEN ? ELSE telefone END,
                email = CASE WHEN email = \'\' THEN ? ELSE email END
            WHERE id = ?
        ')->execute([
            $det['cnpj'] ?? '', $det['razao_social'] ?? '', $det['data_abertura'] ?? null, $det['capital_social'] ?? null,
            $situacao, $porte, $socios, $endereco, $ufResp, $telefone, $email, $id,
        ]);

        $stmt = $pdo->prepare('SELECT * FROM prospects WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true, 'prospect' => mapProspectRow($stmt->fetch())]);
        break;
    }

    case 'list_prospects': {
        if ($isAdmin) {
            $rows = $pdo->query('SELECT * FROM prospects ORDER BY created_at DESC')->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT * FROM prospects WHERE vendedor = ? ORDER BY created_at DESC');
            $stmt->execute([$meuNome]);
            $rows = $stmt->fetchAll();
        }
        echo json_encode(array_map('mapProspectRow', $rows));
        break;
    }

    case 'save_prospect_response': {
        $id = reqStr($input['id'] ?? null, 'id');
        $resposta = reqStr($input['resposta'] ?? null, 'resposta', false);
        $status = reqStr($input['status'] ?? null, 'status', false);

        $stmt = $pdo->prepare('SELECT * FROM prospects WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) fail(404, 'Prospect não encontrado.');
        if (!$isAdmin && trim($p['vendedor']) !== trim($meuNome)) {
            fail(403, 'Você só pode editar os próprios prospects.');
        }

        $novoStatus = $status !== '' ? $status : $p['status'];
        $pdo->prepare('UPDATE prospects SET resposta = ?, status = ? WHERE id = ?')
            ->execute([$resposta, $novoStatus, $id]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'prospect_generate_message': {
        $id = reqStr($input['id'] ?? null, 'id');
        $canal = reqStr($input['canal'] ?? null, 'canal'); // 'email' ou 'whatsapp'

        $stmt = $pdo->prepare('SELECT * FROM prospects WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) fail(404, 'Prospect não encontrado.');
        if (!$isAdmin && trim($p['vendedor']) !== trim($meuNome)) {
            fail(403, 'Você só pode gerar mensagens para os próprios prospects.');
        }

        $empresaNome = $config['email_from_name'] ?? 'a empresa';
        $fraseEmpresa = $config['frase_padrao'] ?? '';

        $contexto = "Empresa prospectada: {$p['nome']}\n"
            . "Endereço: {$p['endereco']}\n"
            . "Ramo de atuação buscado: {$p['ramo']}\n"
            . "Cidade: {$p['cidade']}\n"
            . ($p['resposta'] ? "Resposta que essa empresa já deu anteriormente: \"{$p['resposta']}\"\n" : '')
            . "\nEscreva uma mensagem de primeiro contato (abordagem fria) para essa empresa, via "
            . ($canal === 'email' ? 'e-mail' : 'WhatsApp') . ", se apresentando e despertando interesse em "
            . "conhecer os serviços.";

        if ($canal === 'email') {
            $system = "Você é um SDR (pré-vendas) de {$empresaNome}. Escreva um e-mail curto e profissional "
                . "de primeiro contato (cold e-mail) para uma empresa prospectada, cujo único objetivo é "
                . "conseguir uma resposta ou agendar uma conversa — não é para vender tudo de uma vez. "
                . "Retorne EXATAMENTE neste formato, sem texto antes ou depois:\nASSUNTO: <linha de "
                . "assunto>\nCORPO:\n<corpo do e-mail>\nO corpo deve ser objetivo (4-6 frases), sem ser "
                . "genérico, mencionando o ramo de atuação da empresa. Termine com uma pergunta clara ou "
                . "convite para uma breve conversa."
                . ($fraseEmpresa ? " Pode encaixar, se fizer sentido, uma variação desta frase "
                    . "institucional: \"{$fraseEmpresa}\"." : '');
        } else {
            $system = "Você é um SDR (pré-vendas) de {$empresaNome}. Escreva uma mensagem curta e direta de "
                . "primeiro contato via WhatsApp para uma empresa prospectada — informal, como uma "
                . "mensagem real de WhatsApp, 3-5 frases, mencionando o ramo de atuação da empresa, sem "
                . "parecer robótica nem spam. Termine com uma pergunta simples que gere resposta. Não use "
                . "aspas ao redor do texto nem assinatura formal."
                . ($fraseEmpresa ? " Pode encaixar, se fizer sentido, uma variação desta frase "
                    . "institucional: \"{$fraseEmpresa}\"." : '');
        }

        $texto = callAnthropic($config, $system, $contexto, 500);

        $assunto = '';
        $corpo = $texto;
        if ($canal === 'email' && preg_match('/ASSUNTO:\s*(.+?)\n+CORPO:\s*(.+)/is', $texto, $m)) {
            $assunto = trim($m[1]);
            $corpo = trim($m[2]);
        }

        $pdo->prepare('UPDATE prospects SET mensagem_sugerida = ? WHERE id = ?')->execute([$corpo, $id]);

        echo json_encode(['assunto' => $assunto, 'mensagem' => $corpo]);
        break;
    }

    case 'convert_prospect_to_lead': {
        $id = reqStr($input['id'] ?? null, 'id');
        $nomeContato = reqStr($input['nomeContato'] ?? null, 'nomeContato');
        $telefone = reqStr($input['telefone'] ?? null, 'telefone');
        $email = reqStr($input['email'] ?? null, 'email', false);

        $stmt = $pdo->prepare('SELECT * FROM prospects WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) fail(404, 'Prospect não encontrado.');
        if (!$isAdmin && trim($p['vendedor']) !== trim($meuNome)) {
            fail(403, 'Você só pode converter os próprios prospects.');
        }
        if ($p['contact_id']) {
            fail(409, 'Este prospect já foi convertido em lead.');
        }

        $contactId = uid();
        $pdo->prepare('
            INSERT INTO contacts (id, nome, empresa, cargo, telefone, email, origem, disc, vendedor, tags, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            $contactId, $nomeContato, $p['nome'], '', $telefone, $email, 'Prospecção IA', '', $p['vendedor'],
            '[]', date('Y-m-d H:i:s'),
        ]);

        $pdo->prepare('UPDATE prospects SET status = ?, contact_id = ? WHERE id = ?')
            ->execute(['virou_lead', $contactId, $id]);

        echo json_encode(['ok' => true, 'contactId' => $contactId]);
        break;
    }

    case 'delete_prospect': {
        $id = reqStr($input['id'] ?? null, 'id');
        $stmt = $pdo->prepare('SELECT vendedor FROM prospects WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) fail(404, 'Prospect não encontrado.');
        if (!$isAdmin && trim($p['vendedor']) !== trim($meuNome)) {
            fail(403, 'Você só pode remover os próprios prospects.');
        }
        $pdo->prepare('DELETE FROM prospects WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    case 'delete_user': {
        requireAdmin($currentUser);
        $id = reqStr($input['id'] ?? null, 'id');
        if ($currentUser['id'] !== null && (int)$id === (int)$currentUser['id']) {
            fail(400, 'Você não pode remover a própria conta.');
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    default:
        fail(400, 'Ação desconhecida: ' . $action);
}
} catch (Throwable $e) {
    fail(500, 'Erro interno: ' . $e->getMessage() . ' em ' . basename($e->getFile()) . ':' . $e->getLine());
}
