# Fluxio

Projeto desenvolvido por Eduardo Carvalho durante o estágio na InovCorp.

O Fluxio é uma aplicação Laravel + Vue para gestão comercial, operacional e financeira. O objetivo é centralizar clientes, fornecedores, contactos, artigos, propostas, encomendas, faturas de fornecedor, calendário, utilizadores, permissões, configurações e logs numa única plataforma.

## Tecnologias Usadas

- PHP 8.3
- Laravel
- Vue 3
- Inertia.js
- Tailwind CSS
- shadcn-vue
- MySQL
- Laravel Fortify
- Spatie Permission
- Spatie Activitylog
- FullCalendar
- DomPDF

## Módulos Preparados

- Autenticação com 2FA
- Dashboard operacional
- Clientes e fornecedores
- Contactos
- Artigos
- Propostas
- Encomendas de clientes
- Encomendas de fornecedores
- Faturas de fornecedor
- Calendário
- Utilizadores e permissões
- Logs de atividade
- Configuração da empresa
- Listas base de configuração
- Exportação de PDFs

## Perfis Demo

O seeder demo cria utilizadores com roles e permissões alinhadas com a sidebar e com os middlewares das rotas.

| Perfil | Email | Password | Acesso principal |
|---|---|---|---|
| Administrador | `admin@fluxio.test` | `Fluxio123!demo` | Acesso total |
| Comercial | `comercial@fluxio.test` | `Fluxio123!demo` | Clientes, contactos, propostas e encomendas de clientes |
| Financeiro | `financeiro@fluxio.test` | `Fluxio123!demo` | Área financeira, faturas de fornecedor, encomendas e propostas em leitura |
| Operações | `operacoes@fluxio.test` | `Fluxio123!demo` | Clientes, fornecedores, contactos, propostas, encomendas, ordens de trabalho, calendário e artigos |

Existe também um utilizador inicial no `DatabaseSeeder`:

- Email: `admin@fluxio.test`
- Password: `Fluxio123!admin`

Para a apresentação/demo, recomenda-se usar o `FluxioDemoSeeder`.

## Como Correr Localmente

1. Clonar o projeto.
2. Executar `composer install`.
3. Executar `npm install`.
4. Criar o ficheiro `.env` com base no `.env.example`.
5. Configurar a ligação à base de dados MySQL.
6. Executar `php artisan key:generate`.
7. Executar `php artisan optimize:clear`.
8. Executar `php artisan permission:cache-reset`.
9. Executar `php artisan migrate:fresh --seed --seeder=FluxioDemoSeeder`.
10. Executar `npm run build`.
11. Abrir `https://fluxio.test`.

## Validação

Antes de apresentar ou entregar alterações, correr:

```bash
php artisan optimize:clear
php artisan permission:cache-reset
php artisan migrate:fresh --seed --seeder=FluxioDemoSeeder
php artisan route:list
php artisan test
npm run build
```

Estado da última validação:

- `php artisan route:list`: passou, 104 rotas.
- `php artisan test: passou, 83 testes e 259 assertions, 2 skipped esperados porque o registo público está desativado.
- `npm run build`: passou.

## Ambiente Local

O projeto foi usado localmente com Laravel Herd.

Se estiver a usar XAMPP em simultâneo, recomenda-se manter apenas o MySQL ativo e evitar o Apache, para não criar conflito com o domínio `fluxio.test`.

O `.env.example` assume configuração segura para HTTPS. Em desenvolvimento local sem HTTPS, pode ser necessário ajustar variáveis como `SESSION_SECURE_COOKIE` no `.env` local.

## Observações

- Os dados sensíveis usam casts cifrados quando aplicável.
- Documentos e comprovativos ficam fora da pasta pública.
- A navegação é filtrada por permissões reais recebidas do backend.
- Grupos vazios na sidebar não são apresentados.
- Alguns módulos estão preparados como base funcional para evolução futura.

## Autor

Eduardo Carvalho
