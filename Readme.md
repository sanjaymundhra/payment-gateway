** Payment Gateway API (Symfony + Doctrine + JWT + Swagger)**

A simple, modular payment gateway backend built with Symfony, featuring:

✅ User & Account Management

✅ Transactions & Fund Transfers 

✅ JWT Authentication (Lexik JWT)  

✅ API Documentation (Swagger / NelmioApiDocBundle) 

✅ Docker-ready environment 

✅ Clean Domain Structure (Repositories, Controllers, Entities)

📦 Installation

**Clone the repository:**

    git clone https://github.com/your/repo.git
    cd repo


**Install dependencies:**

    composer install


**Copy environment file:**

    cp .env .env.local


**Update your database credentials inside .env.local.**

**Run migrations:**

    php bin/console doctrine:migrations:migrate


**Load initial fixtures (optional):**

    php bin/console doctrine:fixtures:load


**2️⃣ Generate your JWT keys**
    mkdir -p config/jwt
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
    openssl pkey -in config/jwt/private.pem -pubout -out config/jwt/public.pem
    chmod 600 config/jwt/private.pem

**3️⃣ Add JWT passphrase to .env.local**
    JWT_PASSPHRASE=your-passphrase

**4️⃣ Add bundle configuration**

    config/packages/lexik_jwt_authentication.yaml:

**lexik_jwt_authentication:**
        private_key_path: '%kernel.project_dir%/config/jwt/private.pem'
        public_key_path:  '%kernel.project_dir%/config/jwt/public.pem'
        pass_phrase:      '%env(JWT_PASSPHRASE)%'
        token_ttl:        3600

5️⃣ **Update security.yaml**
    security:
        enable_authenticator_manager: true
    
        providers:
            app_user_provider:
                entity:
                    class: App\Entity\User
                    property: email
    
        password_hashers:
            App\Entity\User: 'auto'
    
        firewalls:
            login:
                pattern: ^/api/login
                stateless: true
                anonymous: true
    
            api_login_check:
                pattern: ^/api/login_check
                stateless: true
                anonymous: true
    
            main:
                pattern: ^/api
                stateless: true
                provider: app_user_provider
                jwt: true
    
        access_control:
            - { path: ^/api/login, roles: PUBLIC_ACCESS }
            - { path: ^/api/login_check, roles: PUBLIC_ACCESS }
            - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }

**6️⃣ Expose login route**

    config/routes/jwt.yaml:

    api_login_check:
        path: /api/login_check

🔑** Using JWT Authentication**
➤ **Login to obtain token**
    curl -X POST http://127.0.0.1:8000/api/login_check \
      -H "Content-Type: application/json" \
      -d '{ "username": "admin@example.com", "password": "password123" }'


**Response:**

    {
      "token": "eyJ0eXAiOiJKV1QiLCJh..."
    }

➤ **Use token for API requests**
    curl http://127.0.0.1:8000/api/accounts \
      -H "Authorization: Bearer YOUR_TOKEN_HERE"


**▶ Running the App**

**Start the local server:**

    php -S 127.0.0.1:8000 -t public



**Add to .env.local:**

    REDIS_URL=redis://127.0.0.1:6379


**Start Redis:**

    sudo systemctl start redis
