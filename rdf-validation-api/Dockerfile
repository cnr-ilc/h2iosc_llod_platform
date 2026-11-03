# syntax=docker/dockerfile:1
#
# PLLOD RDF Validation API: dipendenze gestite con uv (pyproject.toml + uv.lock).
#   target "development": anche le dipendenze di test, codice montato da compose, reload automatico
#   target "production":  solo runtime, codice nell'immagine, niente uv, utente non root
#
ARG PYTHON_IMAGE=python:3.15.2-slim-trixie
ARG UV_IMAGE=ghcr.io/astral-sh/uv:0.13.7

FROM ${UV_IMAGE} AS uv

# ---------------------------------------------------------------------------
# base: Python + uv. Il virtualenv sta fuori da /app, così il bind mount del dev non lo nasconde.
# ---------------------------------------------------------------------------
FROM ${PYTHON_IMAGE} AS base

COPY --from=uv /uv /uvx /usr/local/bin/

ENV UV_PROJECT_ENVIRONMENT=/opt/venv \
    UV_PYTHON_DOWNLOADS=never \
    UV_LINK_MODE=copy \
    UV_COMPILE_BYTECODE=1 \
    PATH="/opt/venv/bin:${PATH}" \
    PYTHONUNBUFFERED=1

WORKDIR /app

# ---------------------------------------------------------------------------
# development
# ---------------------------------------------------------------------------
FROM base AS development

RUN --mount=type=cache,target=/root/.cache/uv \
    --mount=type=bind,source=pyproject.toml,target=pyproject.toml \
    --mount=type=bind,source=uv.lock,target=uv.lock \
    uv sync --locked --no-install-project

# Il codice è montato dall'host: niente __pycache__ scritti nella working copy
ENV PYTHONDONTWRITEBYTECODE=1

EXPOSE 8000

CMD ["fastapi", "dev", "src/main.py", "--host", "0.0.0.0", "--port", "8000"]

# ---------------------------------------------------------------------------
# production
# ---------------------------------------------------------------------------
FROM base AS deps

RUN --mount=type=cache,target=/root/.cache/uv \
    --mount=type=bind,source=pyproject.toml,target=pyproject.toml \
    --mount=type=bind,source=uv.lock,target=uv.lock \
    uv sync --locked --no-install-project --no-dev

FROM ${PYTHON_IMAGE} AS production

ENV PATH="/opt/venv/bin:${PATH}" \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

RUN groupadd --gid 10001 app \
    && useradd --uid 10001 --gid app --no-create-home --shell /usr/sbin/nologin app

COPY --from=deps /opt/venv /opt/venv

WORKDIR /app

# Codice e shapes di proprietà di root: l'utente dell'app li può solo leggere (scrive solo in /tmp)
COPY src ./src
COPY shapes ./shapes

USER app

EXPOSE 8000

CMD ["fastapi", "run", "src/main.py", "--host", "0.0.0.0", "--port", "8000"]
