# 🧬 PLLOD — comandi di utilità
#
#   make                 elenco dei comandi
#   make <comando> V=1   mostra anche i comandi docker eseguiti
#   YES=1 make ...       salta le conferme (per script)
#
# Compatibile con GNU Make 3.81 (macOS) e successivi.

SHELL := /bin/bash
.DEFAULT_GOAL := help

DC_DEV  := docker compose -f docker-compose-dev.yml
DC_PROD := docker compose -f docker-compose.yml

ARGS        ?=
SERVICE     ?=
FILE        ?=
BACKUP_DIR  ?= backups
VB_DATA_DIR ?= vocbench3-docker/volumes
TOOLS_IMAGE ?= alpine:3.22

TS           := $(shell date +%Y%m%d-%H%M%S)
LARAVEL_PORT := $(or $(shell sed -n 's/^LARAVEL_PORT=//p' .env 2>/dev/null | tr -d '"' | tail -n 1),8080)
HOST_UID     := $(shell id -u)
HOST_GID     := $(shell id -g)

ifeq ($(V),1)
  Q :=
else
  Q := @
endif

# Conferma digitando una parola (saltata con YES=1): $(call confirm,messaggio,PAROLA)
confirm = if [ "$(YES)" != "1" ]; then read -r -p "⚠️  $(1) Scrivi $(2) per confermare: " ans; [ "$$ans" = "$(2)" ] || { echo "❌ Annullato"; exit 1; }; fi

# Backup del database: $(call db_backup,<docker compose>,<etichetta>)
define db_backup
@echo "💾 Backup del database $(2)..."
$(Q)mkdir -p $(BACKUP_DIR)
$(Q)set -o pipefail; $(1) exec -T mariadb sh -c 'MYSQL_PWD="$$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --single-transaction --routines --triggers --events "$$MARIADB_DATABASE"' | gzip > $(BACKUP_DIR)/db-$(2)-$(TS).sql.gz
@echo "✅ Backup salvato: $(BACKUP_DIR)/db-$(2)-$(TS).sql.gz ($$(du -h $(BACKUP_DIR)/db-$(2)-$(TS).sql.gz | cut -f1))"
endef

# Ripristino del database da FILE (.sql o .sql.gz): $(call db_restore,<docker compose>,<etichetta>)
define db_restore
@test -n "$(FILE)" || { echo "❌ Indica il backup: FILE=$(BACKUP_DIR)/db-....sql.gz"; exit 1; }
@test -f "$(FILE)" || { echo "❌ File non trovato: $(FILE)"; exit 1; }
@$(call confirm,Il database $(2) verrà sovrascritto con $(FILE).,RESTORE)
@echo "♻️  Ripristino di $(FILE) nel database $(2)..."
$(Q)set -o pipefail; if [[ "$(FILE)" == *.gz ]]; then gunzip -c "$(FILE)"; else cat "$(FILE)"; fi | $(1) exec -T mariadb sh -c 'MYSQL_PWD="$$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$$MARIADB_DATABASE"'
@echo "✅ Database $(2) ripristinato"
endef

# Backup dei dati VocBench/GraphDB (bind mount), con i due servizi fermi: $(call vb_backup,<docker compose>,<etichetta>)
define vb_backup
@echo "⏸️  Fermo vocbench3 e graphdb per una copia coerente..."
$(Q)mkdir -p $(BACKUP_DIR)
$(Q)$(1) stop vocbench3 graphdb; \
	echo "📦 Archivio $(VB_DATA_DIR)..."; \
	docker run --rm -v "$(abspath $(VB_DATA_DIR))":/data:ro -v "$(abspath $(BACKUP_DIR))":/backup $(TOOLS_IMAGE) \
		sh -c 'tar -czf /backup/vocbench-$(2)-$(TS).tar.gz -C /data . && chown $(HOST_UID):$(HOST_GID) /backup/vocbench-$(2)-$(TS).tar.gz'; \
	rc=$$?; echo "▶️  Riavvio graphdb e vocbench3..."; $(1) start graphdb vocbench3; \
	[ $$rc -eq 0 ] && echo "✅ Backup salvato: $(BACKUP_DIR)/vocbench-$(2)-$(TS).tar.gz ($$(du -h $(BACKUP_DIR)/vocbench-$(2)-$(TS).tar.gz | cut -f1))" || echo "❌ Backup non riuscito"; exit $$rc
endef

##@ 🧰 Generale

.PHONY: help config-check

help: ## 📖 Mostra questo elenco
	@printf "\n🧬 \033[1mPLLOD\033[0m — comandi di utilità   \033[2m(V=1 mostra i comandi · YES=1 salta le conferme)\033[0m\n"
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo

config-check: ## 🔍 Valida i file docker compose di sviluppo e produzione
	@echo "🔍 Verifico docker-compose-dev.yml..."
	$(Q)$(DC_DEV) config -q
	@echo "🔍 Verifico docker-compose.yml..."
	$(Q)$(DC_PROD) config -q
	@echo "✅ Configurazioni valide"

##@ 💻 Sviluppo · stack (docker-compose-dev.yml)

.PHONY: dev-up dev-down dev-restart dev-build dev-ps dev-logs dev-shell dev-reset

dev-up: ## 🚀 Avvia lo stack di sviluppo (build se serve)
	@echo "🚀 Avvio lo stack di sviluppo..."
	@echo "⏳ Al primo avvio composer install e build degli asset richiedono qualche minuto"
	$(Q)$(DC_DEV) up -d --build --wait --wait-timeout 900
	@echo "✅ Pronto: app http://localhost:$(LARAVEL_PORT) · VocBench http://localhost:1979/vocbench3"

dev-down: ## 🛑 Ferma lo stack di sviluppo (i volumi restano)
	@echo "🛑 Fermo lo stack di sviluppo..."
	$(Q)$(DC_DEV) down
	@echo "✅ Stack fermato"

dev-restart: ## 🔄 Riavvia lo stack o un servizio (SERVICE=laravel_app)
	@echo "🔄 Riavvio $(or $(SERVICE),tutti i servizi)..."
	$(Q)$(DC_DEV) restart $(SERVICE)
	@echo "✅ Riavviato"

dev-build: ## 🏗️  Ricostruisce le immagini di sviluppo (SERVICE=python_api)
	@echo "🏗️  Build delle immagini di sviluppo..."
	$(Q)$(DC_DEV) build $(SERVICE)
	@echo "✅ Build completata"

dev-ps: ## 📋 Stato dei container di sviluppo
	@echo "📋 Container di sviluppo:"
	$(Q)$(DC_DEV) ps

dev-logs: ## 📜 Log in tempo reale (SERVICE=laravel_horizon, vuoto = tutti)
	@echo "📜 Log di $(or $(SERVICE),tutti i servizi) (Ctrl+C per uscire)..."
	$(Q)$(DC_DEV) logs -f --tail=200 $(SERVICE)

dev-shell: ## 🐚 Shell in un container (default laravel_app, SERVICE=python_api)
	@echo "🐚 Shell in $(or $(SERVICE),laravel_app)..."
	$(Q)$(DC_DEV) exec $(or $(SERVICE),laravel_app) sh -c 'command -v bash >/dev/null && exec bash || exec sh'

dev-reset: ## 💣 Ferma lo stack ed elimina i volumi di sviluppo (DB, Redis, storage)
	@$(call confirm,Verranno eliminati i volumi di sviluppo: database Redis e storage.,RESET)
	@echo "💣 Elimino container e volumi di sviluppo..."
	$(Q)$(DC_DEV) down -v
	@echo "✅ Ambiente di sviluppo azzerato: make dev-up per ripartire"

##@ 💻 Sviluppo · Laravel

.PHONY: dev-artisan dev-tinker dev-pail dev-composer dev-bun dev-assets dev-assets-watch dev-migrate dev-fresh \
	dev-optimize-clear dev-horizon-restart dev-queue-failed dev-queue-retry dev-super-admin dev-jobs-test

dev-artisan: ## 🎨 Comando artisan (ARGS="route:list")
	@echo "🎨 php artisan $(ARGS)"
	$(Q)$(DC_DEV) exec laravel_app php artisan $(ARGS)

dev-tinker: ## 🧪 Console Tinker
	@echo "🧪 Tinker (exit per uscire)..."
	$(Q)$(DC_DEV) exec laravel_app php artisan tinker

dev-pail: ## 👀 Log applicativi di Laravel in tempo reale (pail)
	@echo "👀 Log Laravel in tempo reale (Ctrl+C per uscire)..."
	$(Q)$(DC_DEV) exec laravel_app php artisan pail --timeout=0

dev-composer: ## 📦 Comando composer (ARGS="require vendor/pacchetto")
	@echo "📦 composer $(ARGS)"
	$(Q)$(DC_DEV) exec laravel_app composer $(ARGS)

dev-bun: ## 🥟 Comando bun (ARGS="add pacchetto")
	@echo "🥟 bun $(ARGS)"
	$(Q)$(DC_DEV) exec laravel_app bun $(ARGS)

dev-assets: ## 🎨 Compila gli asset con Bun e Vite
	@echo "🎨 Compilo gli asset..."
	$(Q)$(DC_DEV) exec laravel_app bun run build
	@echo "✅ Asset compilati"

dev-assets-watch: ## ♻️  Ricompila gli asset a ogni modifica (vite build --watch)
	@echo "♻️  Watch degli asset (Ctrl+C per uscire)..."
	$(Q)$(DC_DEV) exec laravel_app bun run build --watch

dev-migrate: ## 🗃️  Esegue le migrazioni
	@echo "🗃️  Migrazioni..."
	$(Q)$(DC_DEV) exec laravel_app php artisan migrate $(ARGS)
	@echo "✅ Migrazioni eseguite"

dev-fresh: ## 💥 Ricrea il database di sviluppo da zero con i seeder
	@$(call confirm,Tutte le tabelle del database di sviluppo verranno eliminate.,FRESH)
	@echo "💥 migrate:fresh --seed..."
	$(Q)$(DC_DEV) exec laravel_app php artisan migrate:fresh --seed
	@echo "✅ Database ricreato"

dev-optimize-clear: ## 🧽 Svuota le cache di Laravel (config, route, view, eventi)
	@echo "🧽 Svuoto le cache..."
	$(Q)$(DC_DEV) exec laravel_app php artisan optimize:clear
	@echo "✅ Cache svuotate"

dev-horizon-restart: ## 🧵 Riavvia i worker Horizon (dopo modifiche ai job)
	@echo "🧵 Riavvio Horizon..."
	$(Q)$(DC_DEV) restart laravel_horizon
	@echo "✅ Horizon riavviato"

dev-queue-failed: ## 🚨 Elenca i job falliti
	@echo "🚨 Job falliti:"
	$(Q)$(DC_DEV) exec laravel_app php artisan queue:failed

dev-queue-retry: ## 🔁 Rilancia i job falliti (ARGS=<uuid>, default tutti)
	@echo "🔁 Rilancio i job falliti: $(or $(ARGS),all)"
	$(Q)$(DC_DEV) exec laravel_app php artisan queue:retry $(or $(ARGS),all)

dev-super-admin: ## 👑 Crea un super admin
	@echo "👑 Creazione super admin..."
	$(Q)$(DC_DEV) exec laravel_app php artisan make:super-admin

dev-jobs-test: ## 🧪 Prova i job sui file (ARGS="--job_type=health" o "--file_id=1")
	@echo "🧪 test:resource-file-jobs $(ARGS)"
	$(Q)$(DC_DEV) exec laravel_app php artisan test:resource-file-jobs $(ARGS)

##@ 💻 Sviluppo · test e qualità

.PHONY: dev-test dev-pint dev-py-test dev-ci

dev-test: ## ✅ Test Laravel con Pest (ARGS="--filter=Nome")
	@echo "✅ Test Pest..."
	$(Q)$(DC_DEV) exec laravel_app php vendor/bin/pest $(ARGS)

dev-pint: ## 🧹 Formatta il codice PHP con Pint (ARGS="--test" per il solo controllo)
	@echo "🧹 Pint $(ARGS)..."
	$(Q)$(DC_DEV) exec laravel_app php vendor/bin/pint $(ARGS)

dev-py-test: ## 🐍 Test dell'API Python con pytest (ARGS="-k health")
	@echo "🐍 pytest $(ARGS)..."
	$(Q)$(DC_DEV) exec python_api pytest -p no:cacheprovider $(ARGS)

dev-ci: ## 🧪 Controllo completo: Pint (solo verifica), Pest e pytest
	@echo "🧪 Controllo completo"
	@echo "🧹 Pint --test..."
	$(Q)$(DC_DEV) exec laravel_app php vendor/bin/pint --test
	@echo "✅ Pest..."
	$(Q)$(DC_DEV) exec laravel_app php vendor/bin/pest
	@echo "🐍 pytest..."
	$(Q)$(DC_DEV) exec python_api pytest -p no:cacheprovider -q
	@echo "🎉 Tutto verde"

##@ 💻 Sviluppo · dati e dipendenze

.PHONY: dev-db-shell dev-db-backup dev-db-restore dev-redis-cli dev-vocbench-backup dev-outdated dev-py-upgrade

dev-db-shell: ## 🐬 Client MariaDB sul database di sviluppo
	@echo "🐬 MariaDB (exit per uscire)..."
	$(Q)$(DC_DEV) exec mariadb sh -c 'MYSQL_PWD="$$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$$MARIADB_DATABASE"'

dev-db-backup: ## 💾 Backup del database di sviluppo in backups/
	$(call db_backup,$(DC_DEV),dev)

dev-db-restore: ## ♻️  Ripristina un backup nel database di sviluppo (FILE=...)
	$(call db_restore,$(DC_DEV),dev)

dev-redis-cli: ## 🧠 redis-cli (ARGS="info memory")
	@echo "🧠 redis-cli $(ARGS)"
	$(Q)$(DC_DEV) exec redis redis-cli $(ARGS)

dev-vocbench-backup: ## 💾 Backup dei dati VocBench/GraphDB di sviluppo
	$(call vb_backup,$(DC_DEV),dev)

dev-outdated: ## 📊 Dipendenze aggiornabili (Composer, Bun, uv)
	@echo "📊 Composer (dirette):"
	$(Q)$(DC_DEV) exec laravel_app composer outdated --direct || true
	@echo "📊 Bun:"
	$(Q)$(DC_DEV) exec laravel_app bun outdated || true
	@echo "📊 Python (uv):"
	$(Q)$(DC_DEV) exec python_api uv tree --outdated --depth 1 || true

dev-py-upgrade: ## ⬆️  Aggiorna uv.lock dell'API Python alle ultime versioni
	@echo "⬆️  uv lock --upgrade..."
	$(Q)$(DC_DEV) exec --user $(HOST_UID):$(HOST_GID) -e UV_CACHE_DIR=/tmp/uv-cache python_api uv lock --upgrade
	@echo "📥 Installo le nuove versioni nel container..."
	$(Q)$(DC_DEV) exec python_api uv sync --locked --no-install-project
	@echo "✅ uv.lock aggiornato: make dev-py-test per verificare"

##@ 🏭 Produzione · stack (docker-compose.yml)

.PHONY: prod-deploy prod-up prod-down prod-restart prod-build prod-ps prod-status prod-logs prod-shell

prod-deploy: ## 🚢 Deploy dopo il git pull: backup DB, build, avvio con healthcheck
	@echo "🚢 Deploy di produzione"
	@if $(DC_PROD) ps --status running --services 2>/dev/null | grep -qx mariadb; then \
		$(MAKE) --no-print-directory prod-db-backup; \
	else echo "ℹ️  MariaDB non è in esecuzione: salto il backup"; fi
	@echo "🏗️  Build delle immagini..."
	$(Q)$(DC_PROD) build
	@echo "🚀 Avvio dei container (migrazioni e cache partono da sole all'avvio)..."
	$(Q)$(DC_PROD) up -d --remove-orphans --wait --wait-timeout 600
	@echo "🎉 Deploy completato"
	@$(MAKE) --no-print-directory prod-status

prod-up: ## 🚀 Avvia lo stack di produzione senza ricostruire
	@echo "🚀 Avvio lo stack di produzione..."
	$(Q)$(DC_PROD) up -d --wait --wait-timeout 600
	@echo "✅ Stack avviato"

prod-down: ## 🛑 Ferma lo stack di produzione (i volumi restano)
	@$(call confirm,Il sito di produzione andrà offline.,STOP)
	@echo "🛑 Fermo lo stack di produzione..."
	$(Q)$(DC_PROD) down
	@echo "✅ Stack fermato"

prod-restart: ## 🔄 Riavvia lo stack o un servizio (SERVICE=laravel_app); rilegge il .env
	@echo "🔄 Riavvio $(or $(SERVICE),tutti i servizi)..."
	$(Q)$(DC_PROD) restart $(SERVICE)
	@echo "✅ Riavviato"

prod-build: ## 🏗️  Ricostruisce le immagini di produzione (SERVICE=laravel_app)
	@echo "🏗️  Build delle immagini di produzione..."
	$(Q)$(DC_PROD) build $(SERVICE)
	@echo "✅ Build completata: make prod-up per applicarla"

prod-ps: ## 📋 Stato dei container di produzione
	@echo "📋 Container di produzione:"
	$(Q)$(DC_PROD) ps

prod-status: ## 🩺 Stato: container, risposta dell'app (/up) e Horizon
	@echo "📋 Container:"
	$(Q)$(DC_PROD) ps
	@code=$$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$(LARAVEL_PORT)/up); \
		[ "$$code" = "200" ] && echo "🩺 App http://127.0.0.1:$(LARAVEL_PORT)/up: ✅ $$code" || echo "🩺 App http://127.0.0.1:$(LARAVEL_PORT)/up: ❌ $$code"
	@printf "🧵 Horizon: "
	$(Q)$(DC_PROD) exec -T laravel_horizon php artisan horizon:status --no-ansi | sed -e 's/^ *//' -e '/^$$/d' || true

prod-logs: ## 📜 Log in tempo reale (SERVICE=laravel_horizon, vuoto = tutti)
	@echo "📜 Log di $(or $(SERVICE),tutti i servizi) (Ctrl+C per uscire)..."
	$(Q)$(DC_PROD) logs -f --tail=200 $(SERVICE)

prod-shell: ## 🐚 Shell in un container (default laravel_app, SERVICE=mariadb)
	@echo "🐚 Shell in $(or $(SERVICE),laravel_app)..."
	$(Q)$(DC_PROD) exec $(or $(SERVICE),laravel_app) sh -c 'command -v bash >/dev/null && exec bash || exec sh'

##@ 🏭 Produzione · Laravel

.PHONY: prod-artisan prod-tinker prod-migrate prod-optimize prod-horizon-restart prod-horizon-status \
	prod-queue-failed prod-queue-retry prod-super-admin

prod-artisan: ## 🎨 Comando artisan (ARGS="about")
	@echo "🎨 php artisan $(ARGS)"
	$(Q)$(DC_PROD) exec laravel_app php artisan $(ARGS)

prod-tinker: ## 🧪 Console Tinker (attenzione: dati reali)
	@echo "🧪 Tinker su produzione (exit per uscire)..."
	$(Q)$(DC_PROD) exec laravel_app php artisan tinker

prod-migrate: ## 🗃️  Migrazioni manuali (di norma partono da sole all'avvio)
	@echo "🗃️  migrate --force..."
	$(Q)$(DC_PROD) exec laravel_app php artisan migrate --force
	@echo "✅ Migrazioni eseguite"

prod-optimize: ## ⚡ Rigenera le cache di config, route e view dell'app
	@echo "⚡ optimize:clear + optimize..."
	$(Q)$(DC_PROD) exec laravel_app sh -c 'php artisan optimize:clear && php artisan optimize'
	@echo "✅ Cache rigenerate"

prod-horizon-restart: ## 🧵 Riavvia i worker Horizon finendo i job in corso
	@echo "🧵 horizon:terminate (Docker riavvia il container)..."
	$(Q)$(DC_PROD) exec laravel_horizon php artisan horizon:terminate
	@echo "✅ Horizon in riavvio"

prod-horizon-status: ## 🧵 Stato di Horizon
	$(Q)$(DC_PROD) exec laravel_horizon php artisan horizon:status

prod-queue-failed: ## 🚨 Elenca i job falliti
	@echo "🚨 Job falliti:"
	$(Q)$(DC_PROD) exec laravel_app php artisan queue:failed

prod-queue-retry: ## 🔁 Rilancia i job falliti (ARGS=<uuid>, default tutti)
	@echo "🔁 Rilancio i job falliti: $(or $(ARGS),all)"
	$(Q)$(DC_PROD) exec laravel_app php artisan queue:retry $(or $(ARGS),all)

prod-super-admin: ## 👑 Crea un super admin
	@echo "👑 Creazione super admin..."
	$(Q)$(DC_PROD) exec laravel_app php artisan make:super-admin

##@ 🏭 Produzione · backup e dati

.PHONY: prod-db-backup prod-db-restore prod-db-shell prod-redis-cli prod-vocbench-backup

prod-db-backup: ## 💾 Backup del database di produzione in backups/
	$(call db_backup,$(DC_PROD),prod)

prod-db-restore: ## ♻️  Ripristina un backup nel database di produzione (FILE=...)
	$(call db_restore,$(DC_PROD),prod)

prod-db-shell: ## 🐬 Client MariaDB sul database di produzione
	@echo "🐬 MariaDB di produzione (exit per uscire)..."
	$(Q)$(DC_PROD) exec mariadb sh -c 'MYSQL_PWD="$$MARIADB_ROOT_PASSWORD" exec mariadb -uroot "$$MARIADB_DATABASE"'

prod-redis-cli: ## 🧠 redis-cli (ARGS="info memory")
	@echo "🧠 redis-cli $(ARGS)"
	$(Q)$(DC_PROD) exec redis redis-cli $(ARGS)

prod-vocbench-backup: ## 💾 Backup dei dati VocBench/GraphDB (li ferma durante la copia)
	$(call vb_backup,$(DC_PROD),prod)
