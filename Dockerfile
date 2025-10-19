# 🧩 Laravel + PHP 8.3 + OpenAI
FROM php:8.3-fpm

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libonig-dev libpng-dev libicu-dev && \
    docker-php-ext-install pdo pdo_mysql zip intl mbstring gd

# 设置工作目录
WORKDIR /app

# 拷贝 composer 并安装依赖
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-interaction --no-dev --optimize-autoloader

# 拷贝应用文件
COPY . .

# 开启 Laravel 权限
RUN chmod -R 775 storage bootstrap/cache

# 暴露端口
EXPOSE 8080


# 启动 Laravel 内置服务器
CMD php artisan serve --host=0.0.0.0 --port=8080
