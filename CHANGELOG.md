# Changelog

All notable changes to this project will be documented in this file.

## [1.0.7] - 2024-03-21
### Added
- Lagt til PHP-versjonskrav (7.4+)
- Lagt til WordPress-versjonstesting (6.5)
- PHP-versjonssjekk ved aktivering

## [1.0.6] - 2024-03-21
### Fixed
- Fikset problem med tabellopprettelse på live nettsider
- Flyttet tabellopprettelse til register_activation_hook
- Lagt til bedre feilhåndtering for database-operasjoner

## [1.0.5] - 2024-03-21
### Fixed
- Fikset problem med logging og statistikk
- Lagt til automatisk opprettelse av databasetabeller
- Forbedret håndtering av sesjons-IDer
- Optimalisert database-spørringer

## [1.0.4] - 2024-03-21
### Added
- Automatisk scrolling til bunnen av chat-vinduet
- Smooth scrolling-animasjon for nye meldinger
- Bedre håndtering av scroll-posisjon

### Fixed
- Optimalisert asset-lasting for bedre ytelse
- Fjernet unødvendige debug-meldinger
- Endret "AI is thinking..." til "Nora is thinking..." / "Nora tenker..."
- Fikset scope-problem med thinkingMessage-variabelen

## [1.0.3] - 2024-03-20
### Added
- Støtte for GitHub-token for private repositories
- Mulighet for å velge hvilke FAQ-kategorier som skal inkluderes
- Bedre feilhåndtering og logging

### Fixed
- Fikset problem med manglende sesjoner i databasen
- Forbedret statistikkberegninger
- Optimalisert database-spørringer

## [1.0.2] - 2024-03-19
### Added
- Innstillingslenke i plugins-listen
- Støtte for å velge hvilke FAQ-kategorier som skal inkluderes
- Bedre feilhåndtering og logging

### Fixed
- Fikset problem med manglende sesjoner i databasen
- Forbedret statistikkberegninger
- Optimalisert database-spørringer

## [1.0.1] - 2024-03-18
### Added
- Støtte for å velge hvilke FAQ-kategorier som skal inkluderes
- Bedre feilhåndtering og logging

### Fixed
- Fikset problem med manglende sesjoner i databasen
- Forbedret statistikkberegninger
- Optimalisert database-spørringer

## [1.0.0] - 2024-03-17
### Added
- Grunnleggende chat-funksjonalitet med OpenAI API
- FAQ-integrasjon for bedre svar
- Statistikk og logging
- Admin-panel for innstillinger
- Støtte for flere språk

### Changed
- Improved chat toggle functionality
- Updated chat icons for better UX
- Enhanced error handling

### Fixed
- Fixed chat minimization issues
- Improved security checks
- Better handling of API responses 