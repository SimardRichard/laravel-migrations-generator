# Décisions techniques (ADR condensé)

## 2026-04-24 — Architecture modulaire obligatoire
Tout code métier dans app/Modules/{Name}/. Isolation stricte.

## 2026-04-24 — Reference Models en BD plutôt qu'Enums PHP
Valeurs énumérées métier = tables BD pour modification via admin sans déploiement. Enums PHP réservés aux valeurs purement techniques.

## 2026-04-24 — Module Config en BD plutôt que config/*.php
Configuration métier dans {Name}Config Model. Fichiers config/*.php réservés aux bindings framework.

## 2026-04-24 — UUID v7 + SoftDeletes par défaut
Tous Models métier. Éviter surprises de migration plus tard.

## 2026-04-24 — Spatie Permission + Policies
Rôles/permissions en BD, Policies par Model.

## 2026-04-24 — Pest > PHPUnit, coverage > 80% en CI
Descriptions en anglais. Bloque merge sous le seuil.

## 2026-04-24 — Versions stables uniquement
Composer minimum-stability=stable, prefer-stable=true. Jamais de beta/dev.
