<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$listaCategorias = [
    ['id' => 1, 'nome' => 'Alimentação', 'tipo' => 'despesa'],
    ['id' => 2, 'nome' => 'Lazer', 'tipo' => 'despesa'],
    ['id' => 3, 'nome' => 'Transporte', 'tipo' => 'despesa'],
    ['id' => 4, 'nome' => 'Salário', 'tipo' => 'receita'],
];

$listaLancamentos = [
    ['id' => 1, 'descricao' => 'Mercado', 'valor' => 150.0, 'data' => '2024-07-01', 'tipo' => 'despesa', 'categoria_id' => 1],
    ['id' => 2, 'descricao' => 'Salário julho', 'valor' => 3500.0, 'data' => '2024-07-05', 'tipo' => 'receita', 'categoria_id' => 4],
];

if (!function_exists('responderJson')) {
    function responderJson($resposta, $conteudo, $codigo = 200)
    {
        $resposta->getBody()->write(json_encode($conteudo, JSON_UNESCAPED_UNICODE));

        return $resposta
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($codigo);
    }
}

if (!function_exists('encontrarPorId')) {
    function encontrarPorId(array $itens, int $id)
    {
        foreach ($itens as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('obterProximoId')) {
    function obterProximoId(array $itens)
    {
        return empty($itens) ? 1 : max(array_column($itens, 'id')) + 1;
    }
}

if (!function_exists('validarData')) {
    function validarData(string $data): bool
    {
        $dataConvertida = DateTime::createFromFormat('!Y-m-d', $data);

        return $dataConvertida && $dataConvertida->format('Y-m-d') === $data;
    }
}

$app->get('/status', fn($request, $response) => responderJson($response, ['status' => 'online']));
$app->get('/nome', fn($request, $response) => responderJson($response, ['nome' => 'Maria Eduarda Andrade']));
$app->get('/musica', fn($request, $response) => responderJson($response, ['musica' => 'Bohemian Rhapsody - Queen']));
$app->get('/filme', fn($request, $response) => responderJson($response, ['filme' => 'Interestelar']));
$app->get('/tic', fn($request, $response) => responderJson($response, ['tic' => 'Código Limpo']));

$app->get('/categorias', fn($request, $response) => responderJson($response, ['categorias' => $listaCategorias]));

$app->get('/categorias/{id}', function ($request, $response, $argumentos) use ($listaCategorias) {
    $categoria = encontrarPorId($listaCategorias, (int) $argumentos['id']);

    return $categoria
        ? responderJson($response, $categoria)
        : responderJson($response, ['erro' => 'Categoria não encontrada'], 404);
});

$app->post('/categorias', function ($request, $response) use (&$listaCategorias) {
    $entrada = (array) $request->getParsedBody();
    $nome = trim($entrada['nome'] ?? '');
    $tipo = strtolower(trim($entrada['tipo'] ?? ''));

    if ($nome === '' || !in_array($tipo, ['receita', 'despesa'])) {
        return responderJson($response, ['erro' => 'Informe nome e tipo (receita/despesa)'], 400);
    }

    foreach ($listaCategorias as $categoria) {
        if (strcasecmp($categoria['nome'], $nome) === 0) {
            return responderJson($response, ['erro' => 'Nome de categoria já existe'], 409);
        }
    }

    $novaCategoria = [
        'id' => obterProximoId($listaCategorias),
        'nome' => $nome,
        'tipo' => $tipo,
    ];
    $listaCategorias[] = $novaCategoria;

    return responderJson($response, $novaCategoria, 201);
});

$app->put('/categorias/{id}', function ($request, $response, $argumentos) use (&$listaCategorias, $listaLancamentos) {
    $id = (int) $argumentos['id'];
    $entrada = (array) $request->getParsedBody();
    $nome = trim($entrada['nome'] ?? '');
    $tipo = strtolower(trim($entrada['tipo'] ?? ''));

    if ($nome === '' || !in_array($tipo, ['receita', 'despesa'])) {
        return responderJson($response, ['erro' => 'Dados inválidos'], 400);
    }

    foreach ($listaCategorias as $indice => $categoria) {
        if ($categoria['id'] !== $id) {
            continue;
        }

        foreach ($listaCategorias as $outraCategoria) {
            if ($outraCategoria['id'] !== $id && strcasecmp($outraCategoria['nome'], $nome) === 0) {
                return responderJson($response, ['erro' => 'Nome já existe'], 409);
            }
        }

        foreach ($listaLancamentos as $lancamento) {
            if ($lancamento['categoria_id'] === $id && $lancamento['tipo'] !== $tipo) {
                return responderJson($response, ['erro' => 'Tipo imutável: há lançamentos'], 409);
            }
        }

        $listaCategorias[$indice] = ['id' => $id, 'nome' => $nome, 'tipo' => $tipo];

        return responderJson($response, $listaCategorias[$indice]);
    }

    return responderJson($response, ['erro' => 'Categoria não encontrada'], 404);
});

$app->delete('/categorias/{id}', function ($request, $response, $argumentos) use (&$listaCategorias, $listaLancamentos) {
    $id = (int) $argumentos['id'];

    foreach ($listaCategorias as $indice => $categoria) {
        if ($categoria['id'] !== $id) {
            continue;
        }

        foreach ($listaLancamentos as $lancamento) {
            if ($lancamento['categoria_id'] === $id) {
                return responderJson($response, ['erro' => 'Categoria possui lançamentos vinculados'], 409);
            }
        }

        array_splice($listaCategorias, $indice, 1);

        return responderJson($response, ['mensagem' => 'Categoria excluída']);
    }

    return responderJson($response, ['erro' => 'Categoria não encontrada'], 404);
});

$app->get('/lancamentos', function ($request, $response) use ($listaLancamentos) {
    $filtros = $request->getQueryParams();
    $resultado = array_filter($listaLancamentos, function ($lancamento) use ($filtros) {
        if (isset($filtros['data_inicio']) && $lancamento['data'] < $filtros['data_inicio']) {
            return false;
        }
        if (isset($filtros['data_fim']) && $lancamento['data'] > $filtros['data_fim']) {
            return false;
        }
        if (isset($filtros['categoria_id']) && $lancamento['categoria_id'] !== (int) $filtros['categoria_id']) {
            return false;
        }
        if (isset($filtros['tipo']) && $lancamento['tipo'] !== strtolower($filtros['tipo'])) {
            return false;
        }

        return true;
    });

    return responderJson($response, ['lancamentos' => array_values($resultado)]);
});

$app->get('/lancamentos/{id}', function ($request, $response, $argumentos) use ($listaLancamentos) {
    $lancamento = encontrarPorId($listaLancamentos, (int) $argumentos['id']);

    return $lancamento
        ? responderJson($response, $lancamento)
        : responderJson($response, ['erro' => 'Lançamento não encontrado'], 404);
});

$app->post('/lancamentos', function ($request, $response) use (&$listaLancamentos, $listaCategorias) {
    $entrada = (array) $request->getParsedBody();
    $categoria = encontrarPorId($listaCategorias, (int) ($entrada['categoria_id'] ?? 0));

    $dadosInvalidos = trim($entrada['descricao'] ?? '') === ''
        || !is_numeric($entrada['valor'] ?? null) || (float) $entrada['valor'] <= 0
        || !validarData($entrada['data'] ?? '')
        || !in_array(strtolower($entrada['tipo'] ?? ''), ['receita', 'despesa'])
        || !$categoria || $categoria['tipo'] !== strtolower($entrada['tipo']);

    if ($dadosInvalidos) {
        return responderJson($response, ['erro' => 'Dados inválidos ou categoria incompatível'], 400);
    }

    $novoLancamento = [
        'id' => obterProximoId($listaLancamentos),
        'descricao' => trim($entrada['descricao']),
        'valor' => (float) $entrada['valor'],
        'data' => $entrada['data'],
        'tipo' => strtolower($entrada['tipo']),
        'categoria_id' => $categoria['id'],
    ];
    $listaLancamentos[] = $novoLancamento;

    return responderJson($response, $novoLancamento, 201);
});

$app->put('/lancamentos/{id}', function ($request, $response, $argumentos) use (&$listaLancamentos, $listaCategorias) {
    $id = (int) $argumentos['id'];
    $entrada = (array) $request->getParsedBody();

    foreach ($listaLancamentos as $indice => $lancamento) {
        if ($lancamento['id'] !== $id) {
            continue;
        }

        $lancamentoAtualizado = array_merge($lancamento, $entrada);
        $categoria = encontrarPorId($listaCategorias, (int) ($lancamentoAtualizado['categoria_id'] ?? 0));

        $dadosInvalidos = trim($lancamentoAtualizado['descricao'] ?? '') === ''
            || !is_numeric($lancamentoAtualizado['valor'] ?? null) || (float) $lancamentoAtualizado['valor'] <= 0
            || !validarData($lancamentoAtualizado['data'] ?? '')
            || !in_array($lancamentoAtualizado['tipo'] ?? '', ['receita', 'despesa'])
            || !$categoria || $categoria['tipo'] !== $lancamentoAtualizado['tipo'];

        if ($dadosInvalidos) {
            return responderJson($response, ['erro' => 'Dados inválidos'], 400);
        }

        $listaLancamentos[$indice] = $lancamentoAtualizado;

        return responderJson($response, $lancamentoAtualizado);
    }

    return responderJson($response, ['erro' => 'Lançamento não encontrado'], 404);
});

$app->delete('/lancamentos/{id}', function ($request, $response, $argumentos) use (&$listaLancamentos) {
    foreach ($listaLancamentos as $indice => $lancamento) {
        if ($lancamento['id'] === (int) $argumentos['id']) {
            array_splice($listaLancamentos, $indice, 1);

            return responderJson($response, ['mensagem' => 'Lançamento excluído']);
        }
    }

    return responderJson($response, ['erro' => 'Lançamento não encontrado'], 404);
});

$app->get('/resumo/saldo', function ($request, $response) use ($listaLancamentos) {
    $totalReceitas = 0;
    $totalDespesas = 0;

    foreach ($listaLancamentos as $lancamento) {
        $lancamento['tipo'] === 'receita'
            ? $totalReceitas += $lancamento['valor']
            : $totalDespesas += $lancamento['valor'];
    }

    return responderJson($response, [
        'total_receitas' => $totalReceitas,
        'total_despesas' => $totalDespesas,
        'saldo' => $totalReceitas - $totalDespesas,
    ]);
});

$app->get('/resumo/periodo', function ($request, $response) use ($listaLancamentos) {
    $filtros = $request->getQueryParams();
    $dataInicio = $filtros['data_inicio'] ?? '';
    $dataFim = $filtros['data_fim'] ?? '';

    if (!validarData($dataInicio) || !validarData($dataFim) || $dataInicio > $dataFim) {
        return responderJson($response, ['erro' => 'Datas inválidas (AAAA-MM-DD)'], 400);
    }

    $totalReceitas = 0;
    $totalDespesas = 0;

    foreach ($listaLancamentos as $lancamento) {
        if ($lancamento['data'] >= $dataInicio && $lancamento['data'] <= $dataFim) {
            $lancamento['tipo'] === 'receita'
                ? $totalReceitas += $lancamento['valor']
                : $totalDespesas += $lancamento['valor'];
        }
    }

    return responderJson($response, [
        'data_inicio' => $dataInicio,
        'data_fim' => $dataFim,
        'total_receitas' => $totalReceitas,
        'total_despesas' => $totalDespesas,
        'saldo' => $totalReceitas - $totalDespesas,
    ]);
});

return $app;