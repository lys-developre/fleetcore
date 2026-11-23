# Despliegue de FleetCore

Guía para poner en marcha la demo en `fleetcore.larryyoffre.com`. La producción de una empresa sigue los mismos pasos con su propio dominio.

## Cómo funciona

1. Cada push a `main` con CI verde publica dos imágenes en GitHub Container Registry: `ghcr.io/lys-developre/fleetcore-api` y `ghcr.io/lys-developre/fleetcore-web`, con las etiquetas `latest` y el SHA del commit.
2. El servidor no compila nada. Descarga las imágenes, ejecuta las migraciones y arranca el stack de `compose.prod.yaml`.
3. Caddy obtiene el certificado HTTPS de Let's Encrypt de forma automática.

Las imágenes son públicas, así que el servidor no necesita credenciales de GitHub.

## Requisitos

| Qué | Detalle |
|---|---|
| Servidor | VPS en la UE con Ubuntu 24.04 LTS. Un Hetzner CX22 (2 vCPU, 4 GB) sobra para la demo |
| DNS | Registro `A` de `fleetcore.larryyoffre.com` apuntando a la IP del servidor. Registro `AAAA` si el servidor tiene IPv6 |
| Puertos | 22, 80 y 443 abiertos. Nada más |

## Primera instalación

En el servidor, como un usuario con `sudo`:

```bash
# Docker y el plugin de Compose
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker "$USER"   # cerrar sesión y volver a entrar

# Cortafuegos
sudo ufw allow OpenSSH && sudo ufw allow 80 && sudo ufw allow 443/tcp && sudo ufw allow 443/udp
sudo ufw enable

# Código: solo se usan compose.prod.yaml, infra/caddy y infra/scripts
sudo mkdir -p /opt/fleetcore && sudo chown "$USER" /opt/fleetcore
git clone https://github.com/lys-developre/fleetcore.git /opt/fleetcore
cd /opt/fleetcore

# Configuración
cp .env.prod.example .env.prod
chmod 600 .env.prod
nano .env.prod   # rellenar ACME_EMAIL, POSTGRES_PASSWORD, APP_SECRET y BACKUP_PASSPHRASE

./infra/scripts/deploy.sh
```

El script termina llamando a `https://<DOMAIN>/api/health`. Si responde `"status":"ok"`, la demo está en línea.

## Actualizar

```bash
cd /opt/fleetcore && git pull && ./infra/scripts/deploy.sh
```

Para fijar una versión concreta, poner en `IMAGE_TAG` el SHA del commit en lugar de `latest`.

## Copias de seguridad

```bash
crontab -e
# 15 3 * * * /opt/fleetcore/infra/scripts/backup.sh >> /var/log/fleetcore-backup.log 2>&1
```

El script cifra con `BACKUP_PASSPHRASE` y guarda 30 días en `BACKUP_DIR`. Esa carpeta sigue estando en el mismo servidor: para la producción de una empresa hay que copiarla fuera, por ejemplo con `rclone` a un almacenamiento externo.

## Problemas habituales

| Síntoma | Causa probable |
|---|---|
| Caddy no obtiene certificado | El DNS aún no apunta al servidor o el puerto 80 está cerrado |
| `/api/health` devuelve 503 | La API no llega a PostgreSQL. Ver `docker compose -f compose.prod.yaml logs api db` |
| El worker se reinicia | Falta una migración. Ver `docker compose -f compose.prod.yaml logs worker` |
