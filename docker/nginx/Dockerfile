# Dockerfile prod nginx
FROM nginx:alpine

# Docker Hub не передаёт автоматически эти значения в build-arg — 
# ты должен явно указать их в настройках “Build settings” → “Build args”.
ARG SOURCE_COMMIT
ARG SOURCE_TAG
LABEL org.opencontainers.image.revision=$SOURCE_COMMIT
LABEL org.opencontainers.image.version=$SOURCE_TAG

COPY ./docker/nginx/default.prod.conf /etc/nginx/templates/default.conf.template
COPY . /var/www/html
COPY ./docker/nginx/html /var/www/nginx-html

RUN echo "$(date +%s)" >> /etc/container_build_timestamp
RUN echo $SOURCE_COMMIT >> /etc/container_SOURCE_COMMIT
RUN echo $SOURCE_TAG >> /etc/container_SOURCE_TAG