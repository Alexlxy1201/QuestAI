# === Laravel + PHP 8.3 + OpenAI API ===
FROM php:8.3-fpm

# 系统依赖
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libonig-dev libpng-dev libicu-dev && \
    docker-php-ext-install pdo pdo_mysql zip intl mbstring gd

# 工作目录
WORKDIR /app

# 先复制全部项目（让 artisan 存在）
COPY . .

# 安装 Composer 并安装依赖
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-interaction --no-dev --optimize-autoloader

# 设置文件权限
RUN chmod -R 775 storage bootstrap/cache

# 开放端口
EXPOSE 8080

# 启动 Laravel 内置服务器
CMD php artisan serve --host=0.0.0.0 --port=8080
