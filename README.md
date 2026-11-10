# Controle Financeiro Pessoal — API REST

API em **PHP + Slim Framework** para gerenciar receitas, despesas, categorias e resumo financeiro. Dados ficam em memória (arrays).

---

## Como rodar a API

composer install
php -S localhost:8080 -t public

Acesse em: `http://localhost:8080`

---

## Como rodar os testes

vendor/bin/phpunit tests

---

## Endpoints

### Identificação

| Método | Rota | O que faz |
|--------|------|-----------|
| GET | `/status` | Verifica se a API está online |
| GET | `/nome` | Nome do autor |
| GET | `/musica` | Música favorita |
| GET | `/filme` | Filme favorito |
| GET | `/tic` | Tecnologia favorita |

### Categorias

| Método | Rota | O que faz | Status de sucesso |
|--------|------|-----------|-------------------|
| GET | `/categorias` | Lista todas | 200 |
| GET | `/categorias/{id}` | Busca por ID | 200 |
| POST | `/categorias` | Cria nova | 201 |
| PUT | `/categorias/{id}` | Atualiza | 200 |
| DELETE | `/categorias/{id}` | Exclui | 200 |

Body do POST/PUT:

{ "nome": "Alimentação", "tipo": "despesa" }

### Lançamentos

| Método | Rota | O que faz | Status de sucesso |
|--------|------|-----------|-------------------|
| GET | `/lancamentos` | Lista com filtros | 200 |
| GET | `/lancamentos/{id}` | Busca por ID | 200 |
| POST | `/lancamentos` | Cria novo | 201 |
| PUT | `/lancamentos/{id}` | Atualiza | 200 |
| DELETE | `/lancamentos/{id}` | Exclui | 200 |

Body do POST/PUT:

{
  "descricao": "Mercado",
  "valor": 150.00,
  "data": "2024-07-01",
  "tipo": "despesa",
  "categoria_id": 1
}

Filtros aceitos no GET `/lancamentos`:

?data_inicio=2024-07-01&data_fim=2024-07-31&categoria_id=1&tipo=despesa

### Resumo

| Método | Rota | O que faz |
|--------|------|-----------|
| GET | `/resumo/saldo` | Saldo atual (receitas − despesas) |
| GET | `/resumo/periodo?data_inicio=&data_fim=` | Totais em um período |

---

##  Códigos de status

| Código | Significado |
|--------|-------------|
| 200 | Sucesso |
| 201 | Criado |
| 400 | Dados inválidos |
| 404 | Não encontrado |
| 409 | Conflito (nome duplicado / exclusão bloqueada) |

---

##  Regras de negócio

- **RN01** — Valor do lançamento deve ser maior que zero
- **RN02** — Tipo do lançamento deve ser igual ao da categoria
- **RN03** — Não pode existir categoria com nome repetido
- **RN04** — Lançamento precisa de categoria existente
- **RN05** — Data obrigatória, formato `AAAA-MM-DD`
- **RF05** — Não excluir categoria com lançamentos vinculados

---

##  Estrutura do projeto

.
├── public/
│   └── index.php        # Ponto de entrada
├── src/
│   └── app.php          # Rotas + helpers + dados em memória
├── tests/
│   └── ControleFinanceiroTest.php
├── composer.json
└── README.md

---

##  Como o código está organizado

1. **Dados em memória** → arrays `$categorias` e `$lancamentos`
2. **Helpers** → funções reutilizadas por todas as rotas:
   - `json()` — devolve resposta JSON com status
   - `buscarPorId()` — procura item pelo ID
   - `proximoId()` — gera o próximo ID
   - `dataValida()` — valida formato de data
3. **Rotas** → cada endpoint tem no máximo ~15 linhas