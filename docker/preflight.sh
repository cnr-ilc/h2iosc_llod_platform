#!/usr/bin/env bash
# 🔎 Controllo preliminare della configurazione, eseguito dal Makefile prima di ogni comando dev-* / prod-*.
#
#   docker/preflight.sh dev|prod [--quiet]
#
# Verifica Docker e Compose, il file .env.<ambiente>, le variabili obbligatorie, gli host dei servizi
# rispetto ai container di docker-compose.<ambiente>.yml, le cartelle dati e la validità del compose.
# Con --quiet stampa una sola riga se è tutto a posto (il dettaglio compare solo in caso di errori).
# Non stampa mai i valori di password, chiavi e token. Compatibile con il bash 3.2 di macOS.

set -u

ENV_NAME=${1:-}
QUIET=0
[ "${2:-}" = "--quiet" ] && QUIET=1
case "$ENV_NAME" in dev|prod) ;; *) echo "uso: $0 dev|prod [--quiet]" >&2; exit 2 ;; esac
cd "$(dirname "$0")/.." || exit 2

ENV_FILE=".env.$ENV_NAME"
COMPOSE_FILE="docker-compose.$ENV_NAME.yml"
TEMPLATE="$ENV_FILE.example"

errors=0
warnings=0
lines=()
ok()   { lines+=("  ✅ $*"); }
err()  { lines+=("  ❌ $*"); errors=$((errors + 1)); }
warn() { lines+=("  ⚠️  $*"); warnings=$((warnings + 1)); }

# Valore di una variabile nel file env (ultima definizione, senza virgolette né commenti in coda)
get() {
  local v
  v=$(sed -n "s/^[[:space:]]*$1[[:space:]]*=[[:space:]]*//p" "$ENV_FILE" 2>/dev/null | tail -n 1)
  case "$v" in
    \"*\") v=${v#\"}; v=${v%\"} ;;
    \'*\') v=${v#\'}; v=${v%\'} ;;
    *) v=${v%%[[:space:]]#*} ;;
  esac
  v=${v%"${v##*[![:space:]]}"}
  printf '%s' "$v"
}

# "1 errore" / "2 errori"
n() { if [ "$1" = 1 ]; then printf '1 %s' "$2"; else printf '%s %s' "$1" "$3"; fi; }

# Impostata con un valore reale (non vuota, non null, non un segnaposto del vecchio .env.example)
is_set() { case "$(get "$1")" in ""|null|your-email@example.com|your-password) return 1 ;; esac; }

# ---------------------------------------------------------------------------
# 🐳 Docker e Compose
# ---------------------------------------------------------------------------
docker_ok=0
if ! command -v docker >/dev/null 2>&1; then
  err "Docker non è installato"
elif ! docker info >/dev/null 2>&1; then
  err "Docker non risponde: avvia Docker Desktop / OrbStack / il servizio docker"
elif ! compose_version=$(docker compose version --short 2>/dev/null); then
  err "Docker Compose v2 non disponibile (serve il comando \"docker compose\")"
else
  IFS=. read -r c_major c_minor _ <<< "${compose_version#v}"
  if [ "${c_major:-0}" -lt 2 ] || { [ "${c_major:-0}" -eq 2 ] && [ "${c_minor:-0}" -lt 20 ]; }; then
    err "Docker Compose $compose_version troppo vecchio: serve almeno la 2.20"
  else
    ok "Docker $(docker version --format '{{.Server.Version}}' 2>/dev/null) · Compose $compose_version"
    docker_ok=1
  fi
fi

# ---------------------------------------------------------------------------
# 📄 File env dell'ambiente
# ---------------------------------------------------------------------------
env_ok=0
if [ -d "$ENV_FILE" ]; then
  err "$ENV_FILE è una cartella (Docker la crea quando il file manca): rimuovila con  rmdir $ENV_FILE  e poi  make $ENV_NAME-env-init"
elif [ ! -f "$ENV_FILE" ]; then
  if [ -f .env ]; then
    err "$ENV_FILE non trovato (c'è un .env della vecchia configurazione): crealo con  make $ENV_NAME-env-init"
  else
    err "$ENV_FILE non trovato: crealo con  make $ENV_NAME-env-init  (parte da $TEMPLATE)"
  fi
else
  ok "$ENV_FILE presente"
  env_ok=1
fi

if [ "$env_ok" = 1 ]; then
  # -------------------------------------------------------------------------
  # 🔑 Variabili obbligatorie
  # -------------------------------------------------------------------------
  keys=$(sed -n 's/^[[:space:]]*\([A-Za-z_][A-Za-z0-9_]*\)[[:space:]]*=.*/\1/p' "$ENV_FILE")
  dups=$(printf '%s\n' "$keys" | sort | uniq -d | tr '\n' ' '); dups=${dups% }
  [ -n "$dups" ] && warn "Variabili definite più volte (vale l'ultima, per Laravel e per docker compose): $dups"

  # Docker compose dà la precedenza alle variabili della shell su quelle di --env-file:
  # il database e i container userebbero valori diversi da quelli che legge Laravel
  overridden=""
  for k in $(printf '%s\n' "$keys" | sort -u); do
    if printenv "$k" >/dev/null 2>&1 && [ "$(printenv "$k")" != "$(get "$k")" ]; then overridden="$overridden $k"; fi
  done
  [ -n "$overridden" ] && warn "Impostate anche nella shell con un altro valore (docker compose usa quello della shell):$overridden"

  # Chiavi aggiunte al modello dopo la creazione del file env
  if [ -f "$TEMPLATE" ]; then
    new_keys=""
    for k in $(sed -n 's/^\([A-Za-z_][A-Za-z0-9_]*\)=.*/\1/p' "$TEMPLATE"); do
      printf '%s\n' "$keys" | grep -qx "$k" || new_keys="$new_keys $k"
    done
    [ -n "$new_keys" ] && warn "Variabili di $TEMPLATE assenti in $ENV_FILE:$new_keys"
  fi

  [ -f .env ] && warn "C'è ancora il vecchio .env, che non viene più usato: dopo aver verificato $ENV_FILE puoi rimuoverlo"

  missing=""
  for k in APP_KEY APP_URL DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD DB_ROOT_PASSWORD \
           REDIS_HOST REDIS_PORT QUEUE_CONNECTION SEMANTIC_TURKEY_BASE_URL VOCBENCH_URL GRAPHDB_HOST_URL \
           PY_QUALITY_CHECK_SERVICE_URL DSPACE_BASE_URL; do
    is_set "$k" || missing="$missing $k"
  done
  if [ -n "$missing" ]; then err "Variabili obbligatorie vuote:$missing"; else ok "Variabili obbligatorie impostate"; fi

  key=$(get APP_KEY)
  if [ -n "$key" ]; then
    case "$key" in
      base64:*)
        bytes=$(printf '%s' "${key#base64:}" | openssl base64 -d -A 2>/dev/null | wc -c | tr -d ' ')
        [ "$bytes" = 32 ] || err "APP_KEY non valida: attesi 32 byte in base64 (genera con: echo \"base64:\$(openssl rand -base64 32)\")" ;;
      *) [ ${#key} -eq 32 ] || err "APP_KEY non valida: usa il formato base64:... (genera con: echo \"base64:\$(openssl rand -base64 32)\")" ;;
    esac
  fi

  [ "$(get QUEUE_CONNECTION)" = redis ] || [ -z "$(get QUEUE_CONNECTION)" ] || err "QUEUE_CONNECTION deve essere redis: Horizon elabora solo le code Redis"
  [ "$(get DB_USERNAME)" = root ] && warn "DB_USERNAME=root: l'immagine MariaDB rifiuta di inizializzare un database nuovo con l'utente root"

  app_url=$(get APP_URL)
  redirect=$(get KEYCLOAK_REDIRECT_URI)
  redirect=${redirect//\$\{APP_URL\}/$app_url}
  if [ -n "$redirect" ] && [ -n "$app_url" ]; then
    case "$redirect" in "$app_url"*) ;; *) warn "KEYCLOAK_REDIRECT_URI ($redirect) non è sotto APP_URL ($app_url)" ;; esac
  fi

  # -------------------------------------------------------------------------
  # 🏭 / 💻 Regole specifiche dell'ambiente
  # -------------------------------------------------------------------------
  app_env=$(get APP_ENV)
  app_debug=$(get APP_DEBUG)
  if [ "$ENV_NAME" = prod ]; then
    [ "$app_env" = production ] || err "APP_ENV deve essere production (ora: ${app_env:-vuota})"
    case "$app_debug" in false|0|"") ;; *) err "APP_DEBUG deve essere false in produzione" ;; esac
    case "$app_url" in https://*|"") ;; *) warn "APP_URL non usa https: $app_url" ;; esac
    case "$app_url" in *localhost*|*127.0.0.1*) warn "APP_URL punta a localhost: in produzione serve l'indirizzo pubblico" ;; esac
    is_set VOCBENCH_AUTH_URL || err "VOCBENCH_AUTH_URL vuota: serve l'indirizzo pubblico della pagina di login di VocBench"
    for k in VOCBENCH_URL VOCBENCH_AUTH_URL; do
      case "$(get "$k")" in *localhost*|*127.0.0.1*) err "$k punta a localhost: in produzione serve l'indirizzo pubblico del reverse proxy" ;; esac
    done
    ext_missing=""
    for k in KEYCLOAK_BASE_URL KEYCLOAK_REALM KEYCLOAK_CLIENT_ID KEYCLOAK_CLIENT_SECRET KEYCLOAK_REDIRECT_URI \
             KEYCLOAK_WELL_KNOWN_URL DSPACE_USERNAME DSPACE_PASSWORD SEMANTIC_TURKEY_ADMIN_EMAIL SEMANTIC_TURKEY_ADMIN_PASSWORD; do
      is_set "$k" || ext_missing="$ext_missing $k"
    done
    if [ -n "$ext_missing" ]; then err "Credenziali dei servizi esterni vuote:$ext_missing"; else ok "Credenziali Keycloak, DSpace e VocBench impostate"; fi
    for k in DB_PASSWORD DB_ROOT_PASSWORD; do
      v=$(get "$k")
      case "$v" in password|root|secret|changeme|admin) warn "$k è una password di default" ;; esac
      [ -n "$v" ] && [ ${#v} -lt 12 ] && warn "$k è corta (meno di 12 caratteri)"
    done
  else
    [ "$app_env" = production ] && warn "APP_ENV=production nell'ambiente di sviluppo"
    { is_set DSPACE_USERNAME && is_set DSPACE_PASSWORD; } || warn "DSPACE_USERNAME/DSPACE_PASSWORD vuote: l'import da DSpace non funzionerà"
    { is_set KEYCLOAK_CLIENT_ID && is_set KEYCLOAK_CLIENT_SECRET; } || warn "KEYCLOAK_CLIENT_ID/KEYCLOAK_CLIENT_SECRET vuote: resta solo il login locale"
    { is_set SEMANTIC_TURKEY_ADMIN_EMAIL && is_set SEMANTIC_TURKEY_ADMIN_PASSWORD; } || warn "SEMANTIC_TURKEY_ADMIN_EMAIL/PASSWORD vuote: i job VocBench (utenti e progetti) falliranno"
    port=$(get LARAVEL_PORT); port=${port:-8080}
    case "$app_url" in "http://localhost:$port"|"http://localhost:$port/"|"http://127.0.0.1:$port"*) ;;
      *) warn "APP_URL ($app_url) non corrisponde all'app pubblicata su http://localhost:$port" ;; esac
  fi

  # -------------------------------------------------------------------------
  # 🧬 Architettura: piattaforma delle immagini rispetto a quella di Docker
  # -------------------------------------------------------------------------
  # Come per docker compose, DOCKER_DEFAULT_PLATFORM della shell vince su quella del file env;
  # se manca i compose usano linux/amd64
  platform=$(printenv DOCKER_DEFAULT_PLATFORM 2>/dev/null || get DOCKER_DEFAULT_PLATFORM)
  platform=${platform:-linux/amd64}
  host_platform=""
  platform_ok=0
  [ "$docker_ok" = 1 ] && host_platform=$(docker version --format '{{.Server.Os}}/{{.Server.Arch}}' 2>/dev/null)
  case "$platform" in
    linux/amd64|linux/arm64)
      platform_ok=1
      case "$host_platform" in
        "") ;;
        "$platform") ok "Architettura: immagini $platform, native per Docker $host_platform" ;;
        linux/amd64|linux/arm64)
          if [ "$ENV_NAME" = prod ]; then
            err "Le immagini sono $platform ma Docker gira su $host_platform: in produzione andrebbero in emulazione, lente e instabili. Imposta DOCKER_DEFAULT_PLATFORM=$host_platform in $ENV_FILE"
          else
            warn "Immagini $platform su Docker $host_platform: girano in emulazione, molto più lente. Per immagini native imposta DOCKER_DEFAULT_PLATFORM=$host_platform in $ENV_FILE (make dev-up le ricostruisce)"
          fi ;;
        *) err "Docker gira su $host_platform: le immagini dello stack esistono solo per linux/amd64 e linux/arm64" ;;
      esac ;;
    *) err "DOCKER_DEFAULT_PLATFORM=$platform non supportata: le immagini dello stack esistono per linux/amd64 e linux/arm64" ;;
  esac

  # -------------------------------------------------------------------------
  # 📁 Cartelle dati (bind mount di VocBench e GraphDB)
  # -------------------------------------------------------------------------
  vb=$(get VB_DATA_DIR); vb=${vb:-./vocbench3-docker/volumes}
  vb_missing=""
  for d in stdata gdbhome; do [ -d "$vb/$d" ] || vb_missing="$vb_missing $vb/$d"; done
  if [ -n "$vb_missing" ]; then err "Cartelle dati mancanti (VB_DATA_DIR):$vb_missing"; else ok "Dati VocBench/GraphDB in $vb"; fi

  # -------------------------------------------------------------------------
  # 🧩 Compose: file valido, variabili interpolate, host dei servizi coerenti
  # -------------------------------------------------------------------------
  if [ ! -f "$COMPOSE_FILE" ]; then
    err "$COMPOSE_FILE non trovato"
  elif [ "$docker_ok" = 1 ]; then
    stderr_file=$(mktemp)
    if cfg=$(docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" config 2>"$stderr_file"); then
      unset_vars=$(sed -n 's/.*The [\\]*"\([A-Za-z0-9_]*\)[\\]*" variable is not set.*/\1/p' "$stderr_file" | sort -u | tr '\n' ' ')
      if [ -n "$unset_vars" ]; then err "$COMPOSE_FILE usa variabili non definite in $ENV_FILE: ${unset_vars% }"; else ok "$COMPOSE_FILE valido"; fi

      # Nomi raggiungibili di un servizio: nome del servizio + container_name
      names_of() {
        printf '%s\n' "$cfg" | awk -v s="$1" '
          /^services:/ { in_s = 1; next }
          /^[^ ]/ { in_s = 0 }
          in_s && /^  [A-Za-z0-9_.-]+:$/ { cur = $1; sub(/:$/, "", cur); if (cur == s) print cur }
          in_s && cur == s && /^    container_name:/ { print $2 }
        ' | tr '\n' ' '
      }
      # check_host VARIABILE servizio porta (porta vuota = la variabile è solo un host)
      check_host() {
        local var=$1 svc=$2 port=$3 val host p allowed a
        val=$(get "$var"); [ -n "$val" ] || return 0
        allowed=$(names_of "$svc")
        case "$val" in
          *://*) host=${val#*://}; host=${host%%/*}; p=""; case "$host" in *:*) p=${host##*:}; host=${host%%:*} ;; esac ;;
          *) host=$val; p=$port ;;
        esac
        for a in $allowed; do
          if [ "$host" = "$a" ]; then
            [ -z "$port" ] || [ "$p" = "$port" ] || { err "$var usa la porta ${p:-predefinita}, ma $svc ascolta sulla $port"; return 1; }
            return 0
          fi
        done
        allowed=${allowed% }
        err "$var punta a \"$host\", che non è un container di $COMPOSE_FILE (usa: ${allowed// / oppure })"
        return 1
      }
      hosts_ok=1
      check_host DB_HOST mariadb "" || hosts_ok=0
      check_host REDIS_HOST redis "" || hosts_ok=0
      check_host SEMANTIC_TURKEY_BASE_URL vocbench3 1979 || hosts_ok=0
      check_host GRAPHDB_HOST_URL graphdb 7200 || hosts_ok=0
      check_host PY_QUALITY_CHECK_SERVICE_URL python_api 8000 || hosts_ok=0
      if is_set DB_PORT && [ "$(get DB_PORT)" != 3306 ]; then err "DB_PORT deve essere 3306 (porta di MariaDB nella rete interna)"; hosts_ok=0; fi
      if is_set REDIS_PORT && [ "$(get REDIS_PORT)" != 6379 ]; then err "REDIS_PORT deve essere 6379 (porta di Redis nella rete interna)"; hosts_ok=0; fi
      [ "$hosts_ok" = 1 ] && ok "Host dei servizi coerenti con i container di $COMPOSE_FILE"

      # Immagini già costruite per un'altra architettura: docker compose le riusa finché non vengono ricostruite
      project=$(printf '%s\n' "$cfg" | sed -n 's/^name: //p' | head -n 1)
      built=$(printf '%s\n' "$cfg" | awk '/^services:/ { s = 1; next } /^[^ ]/ { s = 0 }
        s && /^  [A-Za-z0-9_.-]+:$/ { cur = $1; sub(/:$/, "", cur) } s && /^    build:/ { print cur }')
      stale=""
      [ "$platform_ok" = 1 ] || built=""
      for svc in $built; do
        img_platform=$(docker image inspect --format '{{.Os}}/{{.Architecture}}' "$project-$svc" 2>/dev/null) || continue
        [ "$img_platform" = "$platform" ] || stale="$stale $svc"
      done
      if [ -n "$stale" ]; then
        if [ "$ENV_NAME" = prod ]; then rebuild="make prod-deploy"; else rebuild="make dev-up"; fi
        warn "Immagini costruite per un'altra architettura (serve $platform):$stale. Le ricostruisce $rebuild"
      fi
    else
      err "$COMPOSE_FILE non valido: $(grep -v '^$' "$stderr_file" | head -n 2 | tr '\n' ' ')"
    fi
    rm -f "$stderr_file"
  fi
fi

# ---------------------------------------------------------------------------
# 📦 Archivi VocBench in Git LFS: senza git-lfs il clone ha il puntatore di testo, non lo zip
# ---------------------------------------------------------------------------
vb_zip=vocbench3-docker/14.0.0/vocbench3-14.0.0-full.zip
if [ -f "$vb_zip" ] && head -c 40 "$vb_zip" | grep -q '^version https://git-lfs'; then
  err "$vb_zip è un puntatore Git LFS, non lo zip: la build di VocBench fallirebbe. Installa git-lfs e scarica gli archivi con  git lfs install && git lfs pull"
fi

# ---------------------------------------------------------------------------
# Esito
# ---------------------------------------------------------------------------
if [ "$errors" -gt 0 ]; then
  echo "🔎 Configurazione $ENV_NAME ($ENV_FILE · $COMPOSE_FILE)"
  printf '%s\n' "${lines[@]}"
  echo "❌ Configurazione $ENV_NAME non valida: $(n "$errors" errore errori), $(n "$warnings" avviso avvisi). Correggi e riprova (CHECK=0 salta il controllo)."
  exit 1
fi

if [ "$QUIET" = 1 ]; then
  if [ "$warnings" -gt 0 ]; then
    echo "🔎 Configurazione $ENV_NAME ok · ⚠️  $(n "$warnings" avviso avvisi) (dettagli: make $ENV_NAME-check)"
  else
    echo "🔎 Configurazione $ENV_NAME ok"
  fi
else
  echo "🔎 Configurazione $ENV_NAME ($ENV_FILE · $COMPOSE_FILE)"
  printf '%s\n' "${lines[@]}"
  if [ "$warnings" -gt 0 ]; then echo "✅ Configurazione $ENV_NAME valida ($(n "$warnings" avviso avvisi))"; else echo "✅ Configurazione $ENV_NAME valida"; fi
fi
