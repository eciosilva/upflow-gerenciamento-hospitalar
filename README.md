# 🏥 UpFlow - Sistema de Gestão de Leitos Hospitalares

API REST para gerenciamento de ocupação de leitos hospitalares por pacientes, desenvolvida em Laravel 12 com PostgreSQL.

## 📋 Índice

- [Características](#-características)
- [Tecnologias](#-tecnologias)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação e Execução](#-instalação-e-execução)
- [Estrutura da API](#-estrutura-da-api)
- [Endpoints](#-endpoints)
- [Teste com Postman](#-teste-com-postman)
- [Decisões Técnicas](#-decisões-técnicas)
- [Estrutura do Banco](#-estrutura-do-banco)

## 🚀 Características

- ✅ **API REST completa** para gestão de leitos e pacientes
- ✅ **Validações de negócio** (paciente não pode ocupar múltiplos leitos)
- ✅ **Soft Delete** para histórico de ocupações
- ✅ **Busca por CPF** com formatação automática
- ✅ **Transações** para operações críticas
- ✅ **Seeders inteligentes** para dados de exemplo
- ✅ **Containerização** completa com Docker

## 🛠 Tecnologias

- **Backend**: Laravel 12 (PHP 8.4)
- **Banco de Dados**: PostgreSQL 15
- **Containerização**: Docker + Docker Compose
- **Web Server**: Nginx 1.17
- **Assets**: Vite + Bootstrap 5

## 📋 Pré-requisitos

- Docker Desktop
- Docker Compose
- Git

## ⚡ Instalação e Execução

### 1. Clone o repositório

```bash
git clone <url-do-repositorio>
cd UpFlow
```

### 2. Configure as variáveis de ambiente

O arquivo `.env` já está configurado com as credenciais padrão:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=upflow
DB_USERNAME=upflow
DB_PASSWORD=upflow
```

### 3. Execute o projeto

```bash
docker-compose up -d --build
```

### 4. Aguarde a inicialização

O container executará automaticamente:

- ✅ Instalação das dependências (`composer install`)
- ✅ Geração da chave da aplicação (`php artisan key:generate`)
- ✅ Build dos assets (`npm run build`)
- ✅ Execução das migrations (`php artisan migrate --force`)
- ✅ População da base com dados de exemplo (`php artisan db:seed --force`)

### 5. Acesse a API

- **API**: http://localhost:8099/api/
- **Status**: http://localhost:8099/api/status
- **PostgreSQL**: localhost:5499

## 🌐 Estrutura da API

### Entidades

#### **Leito**

```json
{
  "id": 1,
  "esta_ocupado": true|false,
  "paciente": { "id": 1, "nome": "João Silva", "cpf": "123.456.789-01" } | null,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### **Paciente**

```json
{
  "id": 1,
  "nome": "João Silva",
  "cpf": "123.456.789-01",
  "esta_internado": true|false,
  "leito": { "id": 1 } | null,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### **Ocupação**

```json
{
  "id": 1,
  "paciente": { "id": 1, "nome": "João Silva", "cpf": "123.456.789-01" },
  "leito": { "id": 1 },
  "created_at": "2024-01-01T00:00:00.000000Z"
}
```

## 📡 Endpoints

### **Leitos**

- `GET /api/leitos` - Lista todos os leitos com status de ocupação
- `GET /api/leito/{id}` - Busca leito específico por ID

### **Pacientes**

- `GET /api/pacientes` - Lista todos os pacientes com status de internação
- `GET /api/paciente/{id}` - Busca paciente específico por ID
- `GET /api/paciente?cpf={cpf}` - Busca paciente por CPF

### **Ocupações**

- `POST /api/ocupacao` - Criar nova ocupação (internar paciente)
- `PUT /api/ocupacao` - Transferir paciente entre leitos
- `DELETE /api/ocupacao` - Remover ocupação (alta do paciente)

### **Sistema**

- `GET /api/status` - Status da API

## 🧪 Teste com Postman

Acesse o workspace do Postman com coleção completa de testes:

**🔗 [UpFlow API - Postman Workspace](https://app.getpostman.com/join-team?invite_code=601c47bf300a59dd7b80c0e58c5ed12a0362b70c4bf8e9f6be75ca93c08d5f8d&target_code=7b55f7be6c5811ca8c8a1ecd0aba1b09)**

A coleção inclui:

- ✅ Todos os endpoints documentados
- ✅ Exemplos de requests e responses
- ✅ Testes de validação de negócio
- ✅ Casos de erro e sucesso

## 🔧 Decisões Técnicas

### **PostgreSQL vs MySQL**

**Escolhi PostgreSQL pelos seguintes motivos técnicos:**

#### **1. Índices Condicionais (Partial Indexes)**

```sql
-- PostgreSQL: Suporte nativo para índices parciais
CREATE UNIQUE INDEX unique_paciente_ativo 
ON ocupacoes (paciente_id) 
WHERE deleted_at IS NULL;

-- MySQL: Não suporta índices condicionais nativamente
-- Seria necessário usar triggers ou stored procedures
```

#### **2. Conformidade com SQL**

- PostgreSQL tem maior aderência ao padrão SQL
- Melhor suporte para tipos de dados avançados
- Transações mais robustas

#### **3. Recursos Avançados**

- **JSON nativo**: Melhor performance para dados não-relacionais
- **Arrays**: Suporte nativo para arrays
- **Window Functions**: Funcionalidades analíticas avançadas
- **Full-text search**: Busca textual integrada

#### **4. Soft Deletes + Constraints Únicas**

No nosso caso específico, precisávamos garantir que:

- Um paciente não pode ocupar múltiplos leitos simultaneamente
- Um leito não pode ter múltiplos pacientes simultaneamente
- O histórico de ocupações deve ser preservado (soft deletes)

Com PostgreSQL, isso é elegante:

```sql
-- Garante unicidade apenas para registros ativos
CREATE UNIQUE INDEX unique_paciente_ativo 
ON ocupacoes (paciente_id) 
WHERE deleted_at IS NULL;
```

No MySQL, seria necessário uma solução mais complexa com triggers.

### **Docker + Laravel**

- **Isolamento**: Ambiente consistente entre desenvolvedores
- **Simplicidade**: Um comando para subir todo o stack
- **Produção**: Mesma imagem em todos os ambientes

### **Soft Deletes**

- **Auditoria**: Histórico completo de ocupações
- **Recovery**: Possibilidade de "desfazer" exclusões
- **Relatórios**: Análise temporal das ocupações

## 🗄️ Estrutura do Banco

```sql
-- Leitos (apenas ID + timestamps)
CREATE TABLE leitos (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Pacientes
CREATE TABLE pacientes (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Ocupações com constraints avançadas
CREATE TABLE ocupacoes (
    id SERIAL PRIMARY KEY,
    paciente_id INTEGER REFERENCES pacientes(id),
    leito_id INTEGER REFERENCES leitos(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Índices condicionais para regras de negócio
CREATE UNIQUE INDEX unique_paciente_ativo 
ON ocupacoes (paciente_id) WHERE deleted_at IS NULL;

CREATE UNIQUE INDEX unique_leito_ocupado 
ON ocupacoes (leito_id) WHERE deleted_at IS NULL;
```

## 👨‍💻 Desenvolvimento

### Comandos úteis:

```bash
# Ver logs em tempo real
docker-compose logs -f app

# Executar comandos Artisan
docker exec upflow-app php artisan migrate:status

# Acessar container
docker exec -it upflow-app bash

# Recriar base de dados
docker exec upflow-app php artisan migrate:fresh --seed
```

### Estrutura de arquivos:

```
├── app/
│   ├── Http/Controllers/Api/    # Controllers da API
│   └── Models/                  # Models Eloquent
├── database/
│   ├── migrations/              # Migrations do banco
│   └── seeders/                 # Seeders com dados
├── routes/
│   └── api.php                  # Rotas da API
└── .docker/                     # Configurações Docker
```

---

**Desenvolvido com ❤️ para gestão eficiente de leitos hospitalares.**
