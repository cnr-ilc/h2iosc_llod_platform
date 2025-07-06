# Integrazione con ILC4CLARIN (DSpace)

Per l'integrazione con il Pilota LLOD è necessario implementare una serie di API che permettono l'integrazione con il repository ILC4CLARIN, basato su DSpace.

La lista delle API supportate è la seguente: [DSpace REST API](dspace-clarin-rest-api.md)

## API Minime Richieste

Le API da implementare al minimo sono:

-   Ricerca dei contenuti della persona autenticata (vedi sotto), che rispondono alle chiavi di ricerca specificate
-   Recupero di una singola risorsa per ID, ottenendo sia i suoi metadati che i suoi attachments. Anche in questo caso, per motivi di sicurezza l'API deve rispondere solo se la risorsa è effettivamente di proprietà della persona autenticata.

## Proprietà delle Risorse

Per proprietario della risorsa su ILC4CLARIN si considera il "submitter", cioè chi ha fisicamente caricato la risorsa (non necessariamente l'autore). L'informazione è presente nel metadato `submitter` della struttura `dc.description.provenance` che può vedere solo l'admin locale del DSpace: Apparentemente le API pubbliche non restituiscono questo campo.

## Flusso di Autenticazione

È necessario quindi usare un utente admin. Via chiamate HTTP il flusso è il seguente:

-   `curl -k -H "Content-Type: application/json" --data '{"email":"admin@ilc.cnr.it", "password":"xyz"}' https://dspace-clarin-it.ilc.cnr.it/repository/rest/login`
    -> torna un token id
-   `curl -k -X GET -H "Content-Type: application/json" -H "Accept: application/json" -H "rest-dspace-token: d4a333fb-8590-4f86-bc55-fc7f4251a121" https://dspace-clarin-it.ilc.cnr.it/repository/rest/items/123/metadata`
    -> qui il risultato va filtrato per submitter
-   `curl -k -X POST -H "Content-Type: application/json" -H "rest-dspace-token: d4a333fb-8590-4f86-bc55-fc7f4251a121" https://dspace-clarin-it.ilc.cnr.it/repository/rest/logout`
    -> per chiudere la sessione.
