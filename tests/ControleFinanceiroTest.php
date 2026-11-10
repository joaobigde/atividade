<?php
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class ControleFinanceiroTest extends TestCase
{
    private function enviarRequisicao($metodo, $caminho, $conteudo = null)
    {
        $aplicacao = require __DIR__ . '/../src/app.php';
        $requisicao = (new ServerRequestFactory())->createServerRequest($metodo, $caminho);

        if ($conteudo !== null) {
            $requisicao = $requisicao->withHeader('Content-Type', 'application/json');
            $requisicao->getBody()->write(json_encode($conteudo));
            $requisicao->getBody()->rewind();
        }

        return $aplicacao->handle($requisicao);
    }

    public function testListarCategorias()
    {
        $this->assertSame(200, $this->enviarRequisicao('GET', '/categorias')->getStatusCode());
    }

    public function testCategoriaInexistente()
    {
        $this->assertSame(404, $this->enviarRequisicao('GET', '/categorias/999')->getStatusCode());
    }

    public function testCriarCategoria()
    {
        $this->assertSame(201, $this->enviarRequisicao('POST', '/categorias', [
            'nome' => 'Pets',
            'tipo' => 'despesa',
        ])->getStatusCode());
    }

    public function testCategoriaDuplicada()
    {
        $this->assertSame(409, $this->enviarRequisicao('POST', '/categorias', [
            'nome' => 'Lazer',
            'tipo' => 'despesa',
        ])->getStatusCode());
    }

    public function testExcluirCategoriaComLancamento()
    {
        $this->assertSame(409, $this->enviarRequisicao('DELETE', '/categorias/1')->getStatusCode());
    }

    public function testListarLancamentos()
    {
        $this->assertSame(200, $this->enviarRequisicao('GET', '/lancamentos')->getStatusCode());
    }

    public function testFiltrarPorTipo()
    {
        $resposta = $this->enviarRequisicao('GET', '/lancamentos?tipo=receita');
        $resultado = json_decode((string) $resposta->getBody(), true)['lancamentos'];

        foreach ($resultado as $lancamento) {
            $this->assertSame('receita', $lancamento['tipo']);
        }
    }

    public function testCriarLancamento()
    {
        $this->assertSame(201, $this->enviarRequisicao('POST', '/lancamentos', [
            'descricao' => 'Conta de luz',
            'valor' => 120,
            'data' => '2024-07-20',
            'tipo' => 'despesa',
            'categoria_id' => 3,
        ])->getStatusCode());
    }

    public function testValorZeroInvalido()
    {
        $this->assertSame(400, $this->enviarRequisicao('POST', '/lancamentos', [
            'descricao' => 'X',
            'valor' => 0,
            'data' => '2024-07-20',
            'tipo' => 'despesa',
            'categoria_id' => 1,
        ])->getStatusCode());
    }

    public function testCategoriaIncompativel()
    {
        $this->assertSame(400, $this->enviarRequisicao('POST', '/lancamentos', [
            'descricao' => 'X',
            'valor' => 10,
            'data' => '2024-07-20',
            'tipo' => 'receita',
            'categoria_id' => 1,
        ])->getStatusCode());
    }

    public function testLancamentoInexistente()
    {
        $this->assertSame(404, $this->enviarRequisicao('GET', '/lancamentos/999')->getStatusCode());
    }

    public function testSaldo()
    {
        $resposta = $this->enviarRequisicao('GET', '/resumo/saldo');
        $this->assertSame(200, $resposta->getStatusCode());
        $this->assertArrayHasKey('saldo', json_decode((string) $resposta->getBody(), true));
    }

    public function testPeriodoInvalido()
    {
        $this->assertSame(400, $this->enviarRequisicao(
            'GET',
            '/resumo/periodo?data_inicio=2024-07-31&data_fim=2024-07-01'
        )->getStatusCode());
    }
}