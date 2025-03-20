# Documentação do Projeto Brasilcard

## Visão Geral

Este projeto implementa um sistema de gestão financeira desenvolvido em Laravel. O sistema permite autenticação de usuários, gerenciamento de saldo e transações financeiras através de uma API RESTful.

## Ambiente de Desenvolvimento

### Requisitos de Sistema

| Software | Versão | Finalidade                        |
| -------- | ------ | --------------------------------- |
| PHP      | 8.2.12 | Linguagem de programação          |
| Laravel  | 12     | Framework PHP                     |
| MySQL    | 8.0+   | Sistema de banco de dados         |
| Windows  | 11     | Sistema operacional               |
| XAMPP    | 8.2.12 | Ambiente de desenvolvimento local |
| VSCode   | Atual  | Editor de código                  |
| Postman  | Atual  | Teste de API                      |

## Instalação e Configuração

### Passo 1: Clonar o Repositório

```sh
git clone https://github.com/lezzin/desafio-brasilcard.git
cd desafio-brasilcard
```

### Passo 2: Instalar Dependências

```sh
composer install
```

### Passo 3: Configurar Ambiente

1. Remova o ".example" do arquivo de configuração ".env".

2. Configure as variáveis de ambiente no arquivo `.env`, especialmente as relacionadas ao banco de dados:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brasilcard
DB_USERNAME=root
DB_PASSWORD=
```

3. Gere a chave da aplicação:

```sh
php artisan key:generate
```

### Passo 4: Configurar Banco de Dados

1. Inicie o XAMPP e ative os serviços MySQL e Apache
2. Acesse o phpMyAdmin através de `http://localhost/phpmyadmin`
3. Crie um novo banco de dados chamado `brasilcard`
4. Execute as migrações para criar a estrutura do banco de dados:

```sh
php artisan migrate
```

5. (Opcional) Popule o banco de dados com dados de teste:

```sh
php artisan db:seed
```

Isso criará:

-   1 usuário com saldo negativo
-   2 usuários com saldo aleatório positivo

## Iniciar o Servidor

```sh
php artisan serve
```

O aplicativo estará disponível em `http://127.0.0.1:8000`

## Sistema de Autenticação

### Registrar Novo Usuário

Utilize a rota de registro para criar um novo usuário:

```
POST /api/v1/auth/register
```

Payload de exemplo:

```json
{
    "name": "Usuário Teste",
    "email": "teste@exemplo.com",
    "password": "senha123",
    "password_confirmation": "senha123"
}
```

### Login

```
POST /api/v1/auth/login
```

Payload de exemplo:

```json
{
    "email": "teste@exemplo.com",
    "password": "senha123"
}
```

A resposta incluirá um token de acesso que deve ser usado para autenticar requisições subsequentes.

### Autenticação de Requisições

Para endpoints protegidos, inclua o token no cabeçalho `Authorization`:

```
Authorization: Bearer {seu_token_aqui}
```

## Documentação da API

Este projeto utiliza [Scramble](https://scramble.dedoc.co/) para gerar documentação da API.

Para acessar a documentação:

1. Certifique-se de que o servidor esteja em execução
2. Acesse `http://127.0.0.1:8000/docs/api` em seu navegador
