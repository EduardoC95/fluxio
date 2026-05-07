# Fluxio

Este projeto foi desenvolvido por mim, Eduardo Carvalho, durante o meu estágio na InovCorp.

O objetivo do Fluxio é servir como base para uma aplicação de gestão comercial, operacional e financeira, reunindo várias áreas da empresa no mesmo sistema.

## Tecnologias usadas

- PHP 8.3
- Laravel
- Vue 3
- Tailwind CSS
- shadcn-vue
- MySQL
- Laravel Fortify
- Spatie Permission
- Spatie Activitylog
- FullCalendar
- DomPDF

## O que já está preparado

- Autenticação com 2FA
- Gestão de clientes e fornecedores
- Gestão de contactos
- Gestão de artigos
- Propostas
- Encomendas de clientes
- Encomendas de fornecedores
- Faturas de fornecedor
- Calendário
- Utilizadores e permissões
- Logs
- Configuração da empresa
- Exportação de PDFs

## Como correr o projeto localmente

1. Clonar o projeto
2. Executar `composer install`
3. Executar `npm install`
4. Criar o ficheiro `.env` com base no `.env.example`
5. Configurar a ligação à base de dados MySQL
6. Executar `php artisan key:generate`
7. Executar `php artisan migrate --seed`
8. Executar `npm run build`
9. Abrir `https://fluxio.test`

## Nota sobre o ambiente local

Neste projeto usei o Herd para servir a aplicação localmente.

Se estiver a usar XAMPP ao mesmo tempo, o ideal é deixar apenas o MySQL ativo e não o Apache, para evitar conflito com o domínio `fluxio.test`.

## Acesso inicial

- Email: `admin@fluxio.test`
- Password: `Fluxio123!admin`

## Observações

- Os dados sensíveis estão cifrados.
- Os documentos ficam fora da pasta pública.
- A aplicação está preparada para funcionar com HTTPS.
- Alguns módulos ficaram já preparados na navegação para desenvolvimento futuro.

## Autor
Eduardo Carvalho
