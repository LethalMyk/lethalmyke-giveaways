# Lethal Myke Tools

Portal Laravel para comunidade Lethal Myke com foco em ferramentas para Tibia Rubinot: dashboard, Map Task, Hunt Places, calculadora de charms e imbuements.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite para ambiente local (recomendado para esse workspace)
- MySQL pronto para produção, caso queira migrar a configuração

## Instalação no Windows PowerShell

```powershell
cd c:\DEV_MK\lethalmyke-giveaways
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

## Configuração do banco

Este projeto usa SQLite por padrão para funcionar sem um servidor MySQL ativo no ambiente local.

Arquivo de referência: `.env`

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Se preferir usar MySQL em produção, ajuste as variáveis conforme o exemplo abaixo (sem expor senhas na documentação):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lethalmyke_giveaways
DB_USERNAME=root
DB_PASSWORD=
```

## Compilar assets

```powershell
cd c:\DEV_MK\lethalmyke-giveaways
npm run dev
```

## Acesso com Twitch

1. Crie um aplicativo no [Twitch Developer Console](https://dev.twitch.tv/console/apps) e configure o OAuth Redirect URL exatamente como `http://127.0.0.1:8000/auth/twitch/callback`.
2. No `.env` local, preencha `TWITCH_CLIENT_ID` e `TWITCH_CLIENT_SECRET` com os valores do aplicativo. Não compartilhe nem versione o client secret.
3. Confirme `APP_URL=http://127.0.0.1:8000` e `TWITCH_REDIRECT_URI=http://127.0.0.1:8000/auth/twitch/callback`; depois rode `php artisan config:clear`.
4. Acesse **Entrar / criar conta**. O primeiro acesso cria o usuário; os seguintes autenticam a conta Twitch já vinculada.
5. Para conceder acesso administrativo ao dono do portal, informe o ID numérico da conta Twitch em `TWITCH_ADMIN_IDS`. IDs múltiplos podem ser separados por vírgula. O papel é concedido quando essa conta autentica; contas já existentes mantêm seu papel.

Uma conta autenticada sem Twitch vinculada pode iniciar o vínculo pelo botão **Vincular Twitch**. Uma Twitch associada a outra conta nunca é transferida automaticamente.

## Executar testes

```powershell
cd c:\DEV_MK\lethalmyke-giveaways
php artisan test
```

## Funcionalidades implementadas

- Dashboard com navegação e cards de acesso
- Páginas públicas de ferramentas
- Calculadora de Charm
- Calculadora de Imbuement
- Calculadora de Exercise Weapons com vocação, skill, dummy, double event, loyalty, cargas, tempo e custo
- Map Task com progresso privado por usuário
- Hunt Places com filtros de level, vocação, grupo e busca; detalhes por slug, favoritos e sugestões moderadas
- Administração de hunts, vocações e dos demais catálogos
- Estrutura de migrations para tasks, hunts, charms, imbuements e progresso individual
- Autenticação Twitch mantida e protegida por middleware de admin

### Dados de Hunt Places

As migrations são aditivas e preservam as hunts existentes. Hunts antigas, inclusive o registro demonstrativo `Demon Jungle`, ficam como rascunhos até um administrador conferir os dados, preencher a revisão explícita e publicar. Nenhuma cotação ou informação do Tibia Global é apresentada como oficial do Rubinot.

Administração: **Administração > Hunt Places** para manter hunts e conteúdo relacionado; **Administração > Vocações** para configurar as opções dos filtros. As sugestões públicas entram numa fila pendente e nunca alteram uma hunt automaticamente.

Atualização segura em PowerShell, sem resetar o banco:

```powershell
php artisan migrate
php artisan db:seed --class=VocationSeeder
```

## Hunt Places

- A listagem pública aceita busca por hunt/criatura, level, tamanho do grupo, vocação e inclusão de dados desatualizados; resultados são ordenados no servidor e paginados com filtros na URL.
- Os detalhes usam `/tools/hunt-places/{slug}` e podem ser compartilhados. Favoritos exigem login; sugestões são moderadas e ficam pendentes, sem alterar a hunt.
- Hunts novas começam como rascunho. Para publicar é obrigatório cadastrar uma data de revisão; hunts antigas do catálogo também permanecem ocultas até serem revisadas e publicadas.
- Não há importação automática do Tibia Global nem preços de mercado presumidos. XP, lucro, requisitos, criaturas, suprimentos, loot, imagens e localização são mantidos por administradores para o Rubinot.
- As vocações iniciais configuráveis são Knight, Paladin, Sorcerer, Druid e Monk. Edite-as em Administração > Vocações.
- Após atualizar o projeto, aplique as migrations e cadastre as vocações sem apagar dados:

```powershell
php artisan migrate
php artisan db:seed --class=VocationSeeder
```

- Gerencie hunts em Administração > Hunt Places; cada hunt aceita dados estruturados em JSON para criaturas, requisitos, suprimentos e melhores loots.

## Observações importantes

- A integração da Twitch exige credenciais válidas em `.env`.
- Os dados de Rubinot devem ser revisados antes de publicar em produção.
- A base de ferramentas foi criada para crescer sem quebrar a estrutura atual de giveaways.

## Solução de problemas comuns

- Banco não conecta: confirme se o SQLite foi configurado corretamente em `.env`.
- 500 na primeira rota: rode `php artisan migrate --seed` e verifique se o arquivo `database/database.sqlite` existe.
- Assets não aparecem: rode `npm run build` ou `npm run dev`.
- Login da Twitch falha: preencha `TWITCH_CLIENT_ID`, `TWITCH_CLIENT_SECRET` e `TWITCH_REDIRECT_URI`.
- Preço de Exercise Weapons: a calculadora não consulta cotações em tempo real; informe os preços do seu mundo nos campos da ferramenta.

## Status atual

- Base do portal funcionando em ambiente local
- Páginas públicas disponíveis
- Testes automatizados validados com SQLite local
- Ajustes de conteúdo e administração podem ser ampliados conforme o catálogo do Rubinot for validado.
