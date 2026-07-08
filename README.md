# 📌 Sistema de Empréstimos e Cobranças

> Plataforma web para monitoramento de empréstimos, gestão de carteira, controle de clientes e automação operacional de cobranças.

Este projeto foi desenvolvido para administrar uma operação de empréstimos com foco em **controle financeiro**, **gestão de contratos**, **baixa inteligente de parcelas**, **auditoria de recebimentos** e **segregação de acesso por perfil de usuário**.

A aplicação foi estruturada para cenários reais de operação, priorizando **consistência de cálculos financeiros**, **flexibilidade de schema com MongoDB** e **segurança nas rotinas críticas do sistema**.

---

## 🚀 Visão Geral

O sistema centraliza o fluxo principal de uma operação de crédito:

- cadastro e gestão de clientes;
- criação de empréstimos com regras configuráveis;
- geração automática de cronograma de parcelas;
- edição segura de contratos sem duplicação indevida;
- baixa de parcelas com múltiplos cenários operacionais;
- auditoria das movimentações de recebimento;
- separação de permissões entre administrador e cobrador.

---

## ✨ Principais Funcionalidades

### 👤 Cadastro e Gestão de Clientes
- Cadastro completo de clientes.
- Edição e manutenção dos dados cadastrais.
- Busca com filtros para facilitar a operação.
- Isolamento de visualização por carteira para usuários cobradores.

### 📆 Geração Automatizada de Cronograma de Parcelas
- Geração automática de parcelas via `LoanScheduleService`.
- Suporte aos tipos de juros:
  - simples;
  - fixo;
  - composto.
- Configuração de intervalos:
  - diário;
  - semanal;
  - quinzenal;
  - mensal.
- Regras opcionais para ignorar:
  - sábados;
  - domingos;
  - feriados.

### 🧠 Edição Inteligente de Contratos
- Recalcula o cronograma mantendo a integridade das parcelas já consolidadas.
- Evita duplicação indevida de parcelas em alterações de contrato.
- Sincroniza o cronograma com a seguinte lógica:
  - mesma quantidade: atualiza as existentes;
  - quantidade menor: remove parcelas abertas excedentes;
  - quantidade maior: cria apenas as novas parcelas faltantes.
- Preserva parcelas já pagas e parcelas com `pago_parcial`.

### 💰 Módulo de Baixa Flexível
O sistema já suporta múltiplas regras de recebimento:

- **Pagamento integral**
  - quita a parcela normalmente;
  - registra o recebimento;
  - atualiza o status da parcela e do contrato.

- **Pagamento somente de juros**
  - baixa apenas os juros e multa da parcela atual;
  - cria automaticamente uma nova parcela no final do contrato com o principal ainda pendente.

- **Pagamento parcial**
  - registra o valor parcial recebido;
  - marca a parcela atual como `pago_parcial`;
  - calcula o saldo restante;
  - cria automaticamente uma nova parcela no final do contrato com o saldo remanescente.

### 🔐 Controle de Acesso por Função (RBAC)
O sistema possui perfis com permissões diferentes:

- **Admin**
  - visão total da operação;
  - acesso ao fluxo de caixa;
  - cadastro de cobradores;
  - acesso aos relatórios completos;
  - exclusão de clientes e empréstimos.

- **Cobrador**
  - cadastro de clientes;
  - cadastro e edição de empréstimos;
  - recebimento e baixa de parcelas;
  - acesso restrito à própria carteira;
  - visualização apenas de seus próprios relatórios operacionais.

### 📊 Relatórios e Auditoria
- Registro detalhado das baixas na collection `recebimentos`.
- Armazenamento do usuário responsável por cada baixa.
- Relatório por cobrador com visão administrativa e individual.
- Base pronta para expansão de métricas diárias, mensais e operacionais.

---

## 🛠️ Stack Tecnológica

### Backend
- **Laravel 12**
- **PHP 8.2**
- Estrutura baseada em Controllers, Form Requests, Services e Blade

### Banco de Dados
- **MongoDB 7**
- Integração via **`mongodb/laravel-mongodb`**
- Conexão principal configurada para o banco:
  - `keneddy_admin`

### Frontend
- **Blade**
- **Vite**
- **Tailwind CSS 4**
- **Axios**

### Infraestrutura
- **Docker**
- **Docker Compose**
- **Nginx**
- **PHP-FPM**

---

## ⚙️ Como Executar o Projeto Localmente

### 1. Clonar o repositório
```bash
git clone <URL_DO_REPOSITORIO>
cd "site keneddy"
```

### 2. Subir os containers
```bash
docker-compose up -d
```

### 3. Instalar dependências do backend
```bash
composer install
```

### 4. Instalar dependências do frontend
```bash
npm install
npm run dev
```

### 5. Configurar o ambiente
Crie o arquivo `.env` com base no `.env.example`.

No Linux/macOS:
```bash
cp .env.example .env
```

No Windows PowerShell:
```powershell
Copy-Item .env.example .env
```

### 6. Ajustar as variáveis principais
Exemplo compatível com este projeto:

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mongodb
MONGODB_URI="mongodb://admin:admin123@mongo:27017/?authSource=admin"
MONGODB_DATABASE="keneddy_admin"
```

### 7. Gerar a chave da aplicação
```bash
php artisan key:generate
```

### 8. Executar a aplicação
Se estiver usando a stack dockerizada com Nginx configurado, acesse:

```text
http://localhost:8000
```

---

## 📈 Regras de Negócio e Arquitetura

### 💵 Cálculo baseado em centavos
O sistema trabalha com campos `*_cents` para valores financeiros.

Exemplos:
- `valor_cents`
- `total_cents`
- `juros_cents`
- `multa_cents`
- `principal_pago_cents`
- `valor_recebido_cents`
- `saldo_restante_cents`

Essa estratégia foi adotada para:

- evitar erros de arredondamento com ponto flutuante;
- manter precisão nos cálculos financeiros;
- garantir integridade nos fluxos de cobrança, amortização e baixa.

### 🧾 Trilha de Auditoria
A collection `recebimentos` funciona como histórico operacional das baixas.

Ela registra informações como:
- parcela;
- empréstimo;
- cliente;
- usuário responsável pela baixa;
- data do recebimento;
- valor recebido;
- tipo da baixa.

Essa trilha é importante para:
- conferência operacional;
- relatórios por cobrador;
- auditoria interna;
- evolução futura do sistema.

---

## 🧱 Arquitetura do Projeto

O projeto segue uma divisão clara de responsabilidades:

- **Controllers**
  - coordenam os fluxos HTTP e o salvamento dos dados.
- **Form Requests**
  - centralizam as validações de entrada.
- **Models**
  - representam as collections do MongoDB.
- **Services**
  - encapsulam regras de negócio mais complexas.
- **Blade + JS**
  - compõem a interface administrativa.

### Serviço central de negócio
Um dos componentes mais importantes do projeto é o:

- `LoanScheduleService`

Esse serviço centraliza:
- parse de valores monetários;
- parse de percentuais;
- geração do cronograma;
- suporte a juros simples, fixos e compostos;
- cálculo de multa;
- ajuste de datas de vencimento;
- decoração das parcelas para exibição.

Essa centralização reduz duplicação de regra e facilita manutenção.

---

## 📦 Principais Módulos

### Clientes
- cadastro;
- edição;
- filtros e pesquisa;
- vínculo operacional com a carteira do cobrador.

### Empréstimos
- criação de contratos;
- parametrização de juros;
- parametrização de multa;
- definição de intervalo de cobrança;
- sincronização segura na edição.

### Parcelas
- cronograma persistido em MongoDB;
- baixa integral;
- baixa somente de juros;
- baixa parcial com geração automática de saldo remanescente;
- atualização automática do status do contrato.

### Financeiro
- visão administrativa do caixa;
- lançamentos avulsos de entrada e saída;
- cálculo do capital e principal emprestado.

### Relatórios
- visão consolidada por cobrador;
- total recebido por período;
- quantidade de recebimentos;
- quantidade de empréstimos criados;
- ações recentes da operação.

---

## 🔐 Autenticação e Segurança

O sistema possui autenticação com usuários persistidos no MongoDB e controle por função:

- `admin`
- `cobrador`

Também já possui base para:
- login com sessão;
- logout;
- recuperação de senha;
- redefinição por token;
- preparação estrutural para futuras camadas de validação em duas etapas.

---

## 🎯 Diferenciais Técnicos

- Uso de **MongoDB** com flexibilidade de schema.
- Cálculo monetário baseado em centavos.
- Sincronização inteligente de parcelas para evitar duplicidade.
- Módulo de baixa com múltiplos cenários reais de cobrança.
- Auditoria operacional por usuário.
- Estrutura pronta para RBAC e crescimento da equipe de cobrança.

---

## 🧪 Cenários Operacionais Já Suportados

- cadastro de cliente;
- cadastro de empréstimo com cronograma automático;
- atualização de contrato preservando parcelas consolidadas;
- pagamento integral;
- pagamento somente de juros;
- pagamento parcial com geração automática do saldo restante;
- isolamento de dados por usuário cobrador;
- relatórios operacionais por carteira.

---

## 🔮 Possíveis Evoluções Futuras

A estrutura atual permite expandir o sistema para cenários como:

- notificações automáticas de cobrança;
- dashboards mais analíticos;
- exportação de relatórios;
- autenticação em duas etapas;
- políticas mais granulares;
- indicadores de inadimplência;
- integração com canais externos de comunicação.

---

## 🤝 Objetivo do Projeto

Este sistema foi projetado para servir como uma base sólida para gestão de empréstimos e cobrança, com foco em:

- segurança;
- precisão financeira;
- rastreabilidade;
- automação operacional;
- escalabilidade do negócio.

---

## 📄 Licença

Definir conforme a estratégia de distribuição do projeto.

---

## 👨‍💻 Observação Final

Este README descreve o estado atual da aplicação com base na stack e nas regras de negócio já implementadas no projeto.
