#!/usr/bin/env bash
# 🆕 Crea il file env di un ambiente (make dev-env-init / make prod-env-init).
#
#   docker/env-init.sh dev|prod [file-da-cui-importare]
#
#   dev   parte da .env.dev.example, genera APP_KEY e password del DB e importa le credenziali
#         dei servizi esterni (DSpace, Keycloak, VocBench) dal file indicato (default: il vecchio .env)
#   prod  se il file indicato (default: il vecchio .env) è di un'installazione esistente, cioè ha
#         APP_KEY, lo riprende così com'è (stessa APP_KEY e stesse password del DB dei volumi in uso);
#         altrimenti fa come dev partendo da .env.prod.example
#
# Non sovrascrive mai un file esistente e non stampa mai i valori dei segreti. Alla fine mostra
# il resoconto di docker/preflight.sh con quello che resta da compilare.

set -euo pipefail

ENV_NAME=${1:-}
case "$ENV_NAME" in dev|prod) ;; *) echo "uso: $0 dev|prod [file-da-cui-importare]" >&2; exit 2 ;; esac
cd "$(dirname "$0")/.."

ENV_FILE=".env.$ENV_NAME"
TEMPLATE="$ENV_FILE.example"
SOURCE=${2:-.env}

if [ -e "$ENV_FILE" ]; then
  echo "❌ $ENV_FILE esiste già: modificalo a mano (o rimuovilo per rigenerarlo)"
  exit 1
fi
[ -f "$TEMPLATE" ] || { echo "❌ $TEMPLATE non trovato"; exit 1; }
command -v openssl >/dev/null 2>&1 || { echo "❌ Serve openssl per generare i segreti"; exit 1; }

tmp=$(mktemp "$ENV_FILE.XXXXXX")
trap 'rm -f "$tmp" "$tmp.new"' EXIT

# Valore così com'è scritto (virgolette comprese) dell'ultima definizione di una variabile
raw() { sed -n "s/^[[:space:]]*$1[[:space:]]*=[[:space:]]*//p" "$2" 2>/dev/null | tail -n 1; }
# Valore senza virgolette
val() { local v; v=$(raw "$1" "$2"); v=${v#[\"\']}; v=${v%[\"\']}; printf '%s' "$v"; }
# Imposta KEY=valore nel file temporaneo (sostituisce la riga o la aggiunge in fondo)
set_key() {
  K="$1" V="$2" awk 'BEGIN { k = ENVIRON["K"]; v = ENVIRON["V"] }
    $0 ~ "^[[:space:]]*" k "[[:space:]]*=" { if (!done) print k "=" v; done = 1; next }
    { print }
    END { if (!done) print k "=" v }' "$tmp" > "$tmp.new"
  mv "$tmp.new" "$tmp"
}

generated=()
imported=()

if [ "$ENV_NAME" = prod ] && [ -f "$SOURCE" ] && [ -n "$(val APP_KEY "$SOURCE")" ]; then
  # Produzione esistente: il vecchio .env contiene APP_KEY e password dei volumi in uso
  cp "$SOURCE" "$tmp"
  echo "📥 $ENV_FILE creato da $SOURCE (APP_KEY e password del DB restano quelle in uso)"
else
  cp "$TEMPLATE" "$tmp"
  echo "📄 $ENV_FILE creato da $TEMPLATE"

  [ -n "$(val APP_KEY "$tmp")" ] || { set_key APP_KEY "base64:$(openssl rand -base64 32)"; generated+=(APP_KEY); }
  for k in DB_PASSWORD DB_ROOT_PASSWORD; do
    [ -n "$(val "$k" "$tmp")" ] || { set_key "$k" "$(openssl rand -hex 24)"; generated+=("$k"); }
  done

  # Credenziali dei servizi esterni dal vecchio .env (solo valori reali, non i segnaposto)
  if [ -f "$SOURCE" ]; then
    for k in DSPACE_USERNAME DSPACE_PASSWORD KEYCLOAK_CLIENT_ID KEYCLOAK_CLIENT_SECRET \
             SEMANTIC_TURKEY_ADMIN_EMAIL SEMANTIC_TURKEY_ADMIN_PASSWORD GRAPHDB_USER GRAPHDB_PSW; do
      v=$(val "$k" "$SOURCE")
      case "$v" in ""|null|your-email@example.com|your-password) continue ;; esac
      set_key "$k" "$(raw "$k" "$SOURCE")"
      imported+=("$k")
    done
  fi
fi

chmod 644 "$tmp"   # i container Laravel lo leggono come utente www-data
mv "$tmp" "$ENV_FILE"
trap - EXIT

[ ${#generated[@]} -gt 0 ] && echo "🔐 Generati: ${generated[*]}"
[ ${#imported[@]} -gt 0 ] && echo "📥 Importati da $SOURCE: ${imported[*]}"

# Resoconto completo: le variabili ancora da compilare compaiono come errori o avvisi
echo
bash docker/preflight.sh "$ENV_NAME" || echo "👉 Completa $ENV_FILE e verifica con: make $ENV_NAME-check"
