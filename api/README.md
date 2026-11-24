# API de jogos

Backend REST em PHP para cadastro/login de jogadores, consulta ao catálogo RAWG,
bibliotecas individuais e avaliações. Não inclui páginas ou componentes de
interface.

## Preparação

1. Selecione o banco `bdpoo` no phpMyAdmin e execute
   [`database/001_game_api.sql`](../database/001_game_api.sql).
   As tabelas `api_*`, `games`, `user_games` e `game_reviews` são separadas
   da tabela `usuario` existente.
2. Configure `RAWG_API_KEY` como variável de ambiente do Apache e reinicie o
   Apache. Não coloque a chave no código nem a envie ao navegador. A API devolve
   `503` quando a variável não estiver configurada.
3. Confira se a conexão PDO em `Model/DAO/conexao.php` aponta para o mesmo banco.
4. As rotas amigáveis dependem de `mod_rewrite` e do `.htaccess` permitido no
   Apache. Como alternativa, use `api/index.php?route=auth/register` e troque
   `auth/register` pelo caminho da rota desejada.

Todas as respostas usam JSON. Envie `Content-Type: application/json` nos
endpoints com corpo. Rotas privadas recebem
`Authorization: Bearer <access_token>`.

## Rotas

| Método | Caminho | Acesso | Descrição |
|---|---|---|---|
| POST | `/api/auth/register` | Público | Cria conta e retorna token |
| POST | `/api/auth/login` | Público | Autentica e retorna token |
| POST | `/api/auth/logout` | Token | Revoga o token atual |
| GET | `/api/games?search=zelda&page=1&page_size=20` | Público | Pesquisa na RAWG |
| GET | `/api/games/{rawg_id}` | Público | Busca detalhes na RAWG |
| GET | `/api/library?status=playing` | Token | Lista a biblioteca do jogador |
| POST | `/api/library` | Token | Importa dados RAWG e adiciona jogo |
| PATCH | `/api/library/{rawg_id}` | Token | Atualiza status, horas ou progresso |
| DELETE | `/api/library/{rawg_id}` | Token | Remove jogo da biblioteca |
| GET | `/api/games/{rawg_id}/reviews` | Público | Lista avaliações públicas |
| PUT | `/api/games/{rawg_id}/review` | Token | Cria ou atualiza a própria avaliação |
| DELETE | `/api/games/{rawg_id}/review` | Token | Remove a própria avaliação |

O cadastro recebe `{"name":"Ana","email":"ana@example.com","password":"senha-forte"}`.
Use uma senha de 8 a 72 bytes.
As senhas são armazenadas com `password_hash`; tokens aleatórios são devolvidos
uma vez e somente seus hashes são armazenados no banco. Tokens expiram em 30 dias.

Para adicionar à biblioteca, envie, por exemplo:

```json
{
  "rawg_id": 3498,
  "status": "playing",
  "hours_played": 12.5,
  "progress_percent": 30
}
```

Status aceitos: `wishlist`, `playing`, `up_next`, `backlog`, `paused`,
`completed` e `retired`. Avaliações recebem `{"rating":5,"review":"Muito bom"}`.
Cada jogador pode manter uma avaliação por jogo e o jogo precisa estar na
biblioteca desse jogador.

## Por que esta estrutura

- **Controllers** validam entradas e definem as respostas HTTP sem misturar
  regras de banco ou detalhes da RAWG.
- **Repositories** concentram consultas PDO parametrizadas e persistência.
- **RawgClient** isola a comunicação externa, com timeout e tratamento de erros;
  a chave permanece no servidor.
- **`games` e `user_games` separados** evitam duplicar dados do catálogo para
  cada jogador e permitem que cada um mantenha seu próprio status/progresso.
- **`game_reviews` ligada à biblioteca** permite avaliações públicas e impede
  avaliar um jogo que o jogador não adicionou.
- **Migração SQL separada** cria as tabelas novas sem alterar o CRUD antigo de
  usuários.
