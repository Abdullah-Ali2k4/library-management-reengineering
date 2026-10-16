# Design Recovery: Library Management System

Location: `docs/reverse-engineering/design/`

| # | Deliverable | File(s) |
|---|-------------|---------|
| 1 | Architecture description | [`architecture.md`](architecture.md) |
| 2 | Architecture diagram | [`architecture-diagram.svg`](architecture-diagram.svg), [`.png`](architecture-diagram.png), source [`.mmd`](architecture-diagram.mmd) |
| 3 | Design pattern analysis | [`design-pattern-analysis.md`](design-pattern-analysis.md) |
| 4 | ERD | [`erd.md`](erd.md) (diagram, data dictionary, inconsistencies), [`erd.svg`](erd.svg), [`erd.png`](erd.png), source [`erd.mmd`](erd.mmd) |
| 5 | UML | [`uml/`](uml/README.md): component + package, class (domain model + library classes), 6 sequence diagrams, rendered SVGs in `uml/img/` |
| 6 | Design assessment | [`design-assessment.md`](design-assessment.md) |

**Key results**

- Architecture: page-oriented procedural PHP monolith over MySQL; no layers, no classes, no framework, no tests.
- Patterns genuinely present: Transaction Script, Page Controller, Template View, layout partials, pessimistic locking, parameterized queries. Singleton, MVC, Repository, DI and others were checked and rejected with evidence.
- Database: 5 tables (4 related, `users` standalone), 3 FK relationships, 14 schema-vs-application observations.
- Assessment: strong on SQL safety, escaping and locked transactions; weak on cohesion, coupling and separation of concerns; 12 candidate redesign areas, none implemented.

Diagram sources are Mermaid (rendered by GitHub, GitLab and VS Code); SVG/PNG renderings are included. Line numbers refer to the uploaded project. Related analyses: `../code/cfg/`, `../code/dfg/`, `../code/call-graphs/`.
