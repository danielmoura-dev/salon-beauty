<h1 align="center">
  <br>
  Salon Beauty
  <br>
</h1>

<p align="center">
  Plataforma SaaS completa para gestão de salões de beleza, barbearias e clínicas estéticas.
  <br>
  Agendamentos, financeiro, comissões, link de agendamento público e muito mais.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Vite-8-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
</p>

---

## Funcionalidades

### Agenda & Agendamentos
- Agenda visual por profissional com criação rápida de horários
- Busca de clientes em tempo real durante o agendamento
- Recorrência semanal, quinzenal e mensal
- Status completo: agendado, confirmado, em andamento, concluído, cancelado, falta
- Bloqueio automático de conflitos de horário por profissional

### Link de Agendamento Público (Meu Link)
- Cada salão recebe um slug único — ex.: `/agendar/salao-da-ana`
- Seleção de múltiplos serviços com barra de resumo flutuante
- Login por WhatsApp sem senha — cliente digita o número e é identificado automaticamente
- Filtro de profissionais que executam **todos** os serviços selecionados
- Slots calculados com buffer configurável entre atendimentos
- Agendamentos encadeados por serviço com duração total correta
- Personalização de banner (imagem com recorte 3:1 ou cor sólida) e logo com upload + crop

### Clientes
- Cadastro completo com foto, WhatsApp, aniversário e observações
- Saldo de créditos e débitos por cliente
- Histórico de atendimentos e pedidos
- Busca por nome e telefone

### Profissionais
- Perfil com foto, especialidade e horário de trabalho por dia da semana
- Atribuição de serviços e comissão individual por serviço
- Relatório de comissões e controle de pagamentos
- Vouchers internos para repasse financeiro

### Serviços & Produtos
- Serviços com duração, preço e comissão padrão
- Produtos com estoque e controle de entrada/saída
- Categorias compartilhadas entre serviços, produtos e despesas

### Pedidos & Vendas
- Pedidos com múltiplos itens (serviços e produtos)
- Múltiplos métodos de pagamento por pedido: dinheiro, cartão, PIX, link, cheque
- Taxa sobre cartão de crédito/débito configurável por salão
- Status: aberto, fechado, reaberto, cancelado

### Financeiro
- Lançamento de despesas avulsas e recorrentes com categorias
- Relatório consolidado: receitas, despesas e comissões
- Pagamento de comissões em lote por profissional

### Assinaturas (SaaS)
- Planos via **Stripe** (cartão de crédito) e **Mercado Pago** (PIX)
- Período de trial configurável
- Cupons de desconto por número de meses
- Webhooks para Stripe e Mercado Pago processados em fila
- Portal do cliente Stripe para autogerenciamento

### Sistema de Afiliados
- Código e link de indicação por afiliado
- Comissão percentual sobre assinaturas indicadas
- Desconto automático para o indicado durante N meses
- Painel de controle de comissões e pagamentos para afiliados

### Autenticação
- Registro com verificação por e-mail via código de 6 dígitos
- Login com Google (OAuth via Socialite)
- Middleware de assinatura ativa — acesso bloqueado sem plano válido

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Banco de dados | SQLite (dev) / MySQL / PostgreSQL |
| Frontend | Alpine.js 3, Tailwind CSS 4, Vite 8 |
| Pagamentos | Stripe, Mercado Pago (PIX) |
| E-mail transacional | Resend |
| Login social | Google OAuth (Laravel Socialite) |
| Filas | Database queue (Laravel Queue) |
| Imagens | Cropper.js — logo 1:1 e banner 3:1 |

---

## Multi-Tenancy

Cada salão é um **Tenant** isolado. O trait `BelongsToTenant` aplica um escopo global em todos os models, garantindo que os dados de um salão nunca sejam visíveis para outro. Rotas públicas (link de agendamento) resolvem o tenant pelo `booking_slug` sem autenticação de usuário.

---

## Instalação

### Pré-requisitos

- PHP 8.3+
- Composer
- Node.js 20+ e npm

### Setup rápido

```bash
git clone https://github.com/danielmoura-dev/salon-beauty.git
cd salon-beauty

# Instala dependências, configura .env, gera chave, roda migrations e build do frontend
composer run setup
```

### Passo a passo manual

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Variáveis de ambiente essenciais

```env
APP_URL=http://localhost:8000

# Banco de dados (SQLite por padrão)
DB_CONNECTION=sqlite

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Mercado Pago
MERCADOPAGO_ACCESS_TOKEN=APP_USR-...
MERCADOPAGO_WEBHOOK_SECRET=...

# E-mail (Resend)
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=noreply@seudominio.com

# Google OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://seudominio.com/auth/google/callback
```

---

## Desenvolvimento

```bash
composer run dev
```

Executa em paralelo:

| Processo | Comando |
|---|---|
| Servidor Laravel | `php artisan serve` |
| Fila de jobs | `php artisan queue:listen` |
| Stream de logs | `php artisan pail` |
| Frontend HMR | `npm run dev` |

---

## Testes

```bash
composer run test
```

---

## Estrutura do Projeto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Painel admin (tenants, afiliados, trial)
│   │   ├── Auth/           # Autenticação (e-mail, Google, verificação)
│   │   ├── Public/         # Agendamento público (sem autenticação)
│   │   └── ...             # Um controller por feature
│   └── Middleware/
├── Models/                 # Appointment, Client, Professional, Service,
│                           # Product, Order, Expense, Subscription, Affiliate…
└── Traits/
    └── BelongsToTenant.php # Escopo global de multi-tenancy

database/
└── migrations/             # Schema completo em migrations versionadas

resources/
└── views/
    ├── app/                # Views autenticadas (agenda, clientes, financeiro…)
    ├── auth/               # Login, registro, verificação de e-mail
    ├── components/         # Componentes Blade reutilizáveis
    └── public/             # Link de agendamento público

routes/
└── web.php                 # Rotas agrupadas: auth, app, admin e público
```

---

## Licença

Distribuído sob a licença [MIT](https://opensource.org/licenses/MIT).
