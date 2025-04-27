# Chatbot Nora - WordPress Plugin

En WordPress-plugin som implementerer en AI-drevet chat-funksjonalitet basert på OpenAI.

## Gjeldende Funksjonalitet

- Chat-vindu som kan minimeres/maksimeres
- Lagring av chat-historikk i localStorage
- Brukerautentisering med navn (påkrevd) og epost (valgfritt)
- Avslutningsknapp med to-trinns bekreftelse

## Pågående Arbeid

### Brukerautentisering
- Implementert velkomstmelding ved første åpning
- Skjema for innsamling av brukerinformasjon
- Validering av påkrevde felt
- Lagring av brukerinformasjon i localStorage

### Chat-grensesnitt
- Responsivt design
- Meldingsvisning med forskjellige stiler for bruker og assistent
- Feilhåndtering og brukervennlige meldinger
- Avslutningsknapp med X-symbol som endrer seg til "Avslutt?" ved første klikk

## Teknisk Implementasjon

### Frontend
- JavaScript med jQuery for dynamisk oppførsel
- CSS for styling og animasjoner
- localStorage for persistering av chat-tilstand

### Backend
- WordPress hooks og filters
- AJAX-håndtering for meldingsutveksling
- Database-integrasjon for statistikk og logging

## Neste Steg
1. Implementere statistikk og logging
2. Forbedre feilhåndtering
3. Legge til flere brukervennlige funksjoner
4. Optimalisere ytelse

## Utviklingsmiljø
- WordPress (siste versjon)
- PHP 7.4+
- jQuery
- OpenAI API

## Installasjon
1. Last opp plugin-mappen til `/wp-content/plugins/`
2. Aktiver pluginen i WordPress admin
3. Konfigurer OpenAI API-nøkkel i plugin-innstillingene

## Bidrag
Bidrag er velkomne! Vennligst følg WordPress kodestandarder og dokumenter endringene dine. 