# Registre des modules — CRASSQ

> Référence centrale de tous les modules du projet.
> Chaque module est classifié, priorisé et lié à ses dépendances.
>
> **Légende classification :**
> - 🔴 CRITIQUE — Chiffrement AES-256-GCM obligatoire + audit complet
> - 🟠 SENSIBLE — Chiffrement obligatoire + audit
> - 🟡 INTERNE — Protection standard (TDE)
> - 🟢 PUBLIC — Aucune restriction
>
> **Légende statut :**
> - 📋 Planifié | 🔨 En cours | ✅ Terminé | ⏸️ En attente
>
> **Légende priorité :**
> - P0 = Fondation (doit exister avant tout)
> - P1 = Critique (première itération)
> - P2 = Important (deuxième itération)
> - P3 = Souhaitable (troisième itération)
> - P4 = Futur (backlog)

---

## 1. Noyau plateforme

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `UserManagement` | 🔴 | P0 | 📋 | Core, AuthIam |
| `RolePermissionManagement` | 🟡 | P0 | 📋 | UserManagement |
| `MultiOrganization` | 🟡 | P0 | 📋 | TenantManagement |
| `GlobalSettings` | 🟡 | P0 | 📋 | Core |
| `AuditLog` | 🟠 | P0 | 📋 | Core, Encryption |
| `DocumentManagement` | 🟠 | P1 | 📋 | Encryption, AuditLog |
| `GlobalSearchEngine` | 🟡 | P2 | 📋 | Core |
| `NotificationSystem` | 🟡 | P1 | 📋 | Core, UserManagement |
| `TaskTracking` | 🟡 | P2 | 📋 | UserManagement |
| `ActivityHistory` | 🟠 | P1 | 📋 | AuditLog |
| `ApiGateway` | 🟡 | P1 | 📋 | AuthIam |
| `WebhookEventSystem` | 🟡 | P2 | 📋 | Core |
| `AttachmentManagement` | 🟠 | P1 | 📋 | Encryption, DocumentManagement |
| `SystemObservability` | 🟡 | P1 | 📋 | Core |
| `QueueScheduler` | 🟡 | P0 | 📋 | Core |
| `ClientConfiguration` | 🟡 | P1 | 📋 | TenantManagement |
| `Localization` | 🟢 | P1 | 📋 | Core |
| `TimezoneManagement` | 🟢 | P2 | 📋 | Core |
| `ElectronicSignature` | 🟠 | P3 | 📋 | Encryption, UserManagement |
| `ArchivalRetention` | 🟠 | P2 | 📋 | AuditLog, DocumentManagement |

**Notes catégorie :** Le noyau est P0/P1 car tout le reste en dépend. `AuditLog` et `Encryption` sont les deux modules fondamentaux déjà architecturés dans cette session.

---

## 2. Identité et personnes

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `PersonProfile` | 🔴 | P1 | 📋 | Encryption, AuditLog |
| `AliasManagement` | 🔴 | P1 | 📋 | PersonProfile |
| `PhysicalDescription` | 🟠 | P1 | 📋 | PersonProfile |
| `AddressLocationTracking` | 🔴 | P1 | 📋 | PersonProfile, GpsCoordinates |
| `ContactRelations` | 🟠 | P1 | 📋 | PersonProfile |
| `AffiliationManagement` | 🟠 | P2 | 📋 | PersonProfile |
| `AssociatedVehicles` | 🟠 | P2 | 📋 | PersonProfile, VehicleProfile |
| `IdentityDocuments` | 🔴 | P1 | 📋 | PersonProfile, Encryption |
| `PhotoProfile` | 🔴 | P1 | 📋 | PersonProfile, Encryption (BLOB) |
| `InteractionHistory` | 🟠 | P2 | 📋 | PersonProfile, AuditLog |
| `WantedPersons` | 🔴 | P1 | 📋 | PersonProfile |
| `VulnerablePersons` | 🔴 | P1 | 📋 | PersonProfile |
| `VictimManagement` | 🔴 | P1 | 📋 | PersonProfile, IncidentCreation |
| `WitnessManagement` | 🔴 | P1 | 📋 | PersonProfile, IncidentCreation |
| `SuspectManagement` | 🔴 | P1 | 📋 | PersonProfile, IncidentCreation |
| `ArrestedPersons` | 🔴 | P1 | 📋 | PersonProfile, ArrestRecord |
| `MissingPersons` | 🔴 | P1 | 📋 | PersonProfile |
| `RecidivismTracking` | 🔴 | P2 | 📋 | PersonProfile, IncidentCreation |
| `BiometricManagement` | 🔴 | P3 | 📋 | PersonProfile, Encryption |
| `FingerprintMugshot` | 🔴 | P3 | 📋 | BiometricManagement, Encryption (BLOB) |
| `DnaReference` | 🔴 | P3 | 📋 | BiometricManagement, Encryption |
| `NetworkRelationMapping` | 🔴 | P3 | 📋 | PersonProfile, LinkAnalysis |

**Notes catégorie :** TOUT est 🔴 ou 🟠 — ce sont des données de personnes physiques, souvent judiciarisées. Chiffrement per-agent obligatoire. Accès restreint avec break-glass pour les urgences. Certaines données (biométrie, ADN) nécessitent des contrôles additionnels au-delà du standard.

---

## 3. Incidents et événements

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `IncidentCreation` | 🟠 | P1 | 📋 | Core, UserManagement |
| `EventClassification` | 🟡 | P1 | 📋 | IncidentCreation |
| `PrioritySeverity` | 🟡 | P1 | 📋 | IncidentCreation |
| `AgentAssignment` | 🟠 | P1 | 📋 | IncidentCreation, UserManagement |
| `EventTimeline` | 🟠 | P1 | 📋 | IncidentCreation |
| `LocationInvolvement` | 🟠 | P1 | 📋 | IncidentCreation, GpsCoordinates |
| `PersonInvolvement` | 🔴 | P1 | 📋 | IncidentCreation, PersonProfile |
| `VehicleInvolvement` | 🟠 | P2 | 📋 | IncidentCreation, VehicleProfile |
| `PropertyInvolvement` | 🟠 | P2 | 📋 | IncidentCreation |
| `InvestigationStatus` | 🟡 | P1 | 📋 | IncidentCreation |
| `ActionTracking` | 🟡 | P2 | 📋 | IncidentCreation |
| `IncidentResolution` | 🟠 | P2 | 📋 | IncidentCreation |
| `DuplicateMerge` | 🟠 | P3 | 📋 | IncidentCreation |
| `CaseCorrelation` | 🟠 | P3 | 📋 | IncidentCreation |
| `RelatedIncidents` | 🟡 | P2 | 📋 | IncidentCreation |
| `SupplementaryReports` | 🟠 | P2 | 📋 | IncidentCreation, DocumentManagement |
| `ApprovalWorkflow` | 🟡 | P2 | 📋 | WorkflowEngine |
| `ReportTemplates` | 🟡 | P2 | 📋 | DocumentManagement |
| `NarrativeReports` | 🟠 | P2 | 📋 | IncidentCreation, DocumentManagement |
| `MediaAttachments` | 🟠 | P2 | 📋 | IncidentCreation, Encryption (BLOB) |
| `AnonymizedReports` | 🟡 | P3 | 📋 | IncidentCreation |
| `LegalExport` | 🔴 | P2 | 📋 | IncidentCreation, Encryption |

**Notes catégorie :** `PersonInvolvement` et `LegalExport` sont 🔴 car ils relient des personnes à des incidents — très sensible juridiquement. L'export légal nécessite des contrôles de caviardage et traçabilité.

---

## 4. Appels, dispatch

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `CallCenter` | 🟠 | P2 | 📋 | Core |
| `CallHandling` | 🟠 | P2 | 📋 | CallCenter |
| `DispatchSystem` | 🟠 | P2 | 📋 | CallCenter, AgentAssignment |
| `ActiveUnits` | 🟡 | P2 | 📋 | UserManagement |
| `PatrolStatus` | 🟡 | P2 | 📋 | ActiveUnits |
| `AutoPrioritization` | 🟡 | P3 | 📋 | DispatchSystem, PrioritySeverity |
| `RadioLog` | 🟠 | P3 | 📋 | CallCenter, AuditLog |
| `RealtimeMap` | 🟡 | P3 | 📋 | GpsCoordinates, ActiveUnits |
| `GeoAssignment` | 🟡 | P3 | 📋 | DispatchSystem, GpsCoordinates |
| `ResponseTimeTracking` | 🟡 | P3 | 📋 | DispatchSystem |
| `SupervisorEscalation` | 🟡 | P3 | 📋 | DispatchSystem |
| `OperationalLogs` | 🟠 | P2 | 📋 | AuditLog |
| `VoipIntegration` | 🟡 | P4 | 📋 | TelephonyIntegration |
| `CallRecording` | 🔴 | P3 | 📋 | CallCenter, Encryption (BLOB) |
| `MultiUnitDispatch` | 🟡 | P3 | 📋 | DispatchSystem |
| `OperationalDiary` | 🟠 | P2 | 📋 | AuditLog |

**Notes catégorie :** `CallRecording` est 🔴 car les enregistrements vocaux contiennent potentiellement des informations de victimes/témoins. Stockage BLOB chiffré obligatoire.

---

## 5. Patrouille terrain

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `ShiftManagement` | 🟡 | P2 | 📋 | UserManagement |
| `TimeTracking` | 🟡 | P2 | 📋 | ShiftManagement |
| `PatrolLog` | 🟠 | P2 | 📋 | ShiftManagement, AuditLog |
| `CheckpointRounds` | 🟡 | P3 | 📋 | PatrolLog, GpsCoordinates |
| `PreventiveActivities` | 🟡 | P3 | 📋 | PatrolLog |
| `TrafficControl` | 🟠 | P3 | 📋 | PatrolLog, PersonProfile |
| `PersonChecks` | 🔴 | P2 | 📋 | PatrolLog, PersonProfile |
| `FacilityInspection` | 🟡 | P3 | 📋 | PatrolLog |
| `FieldComplaints` | 🟠 | P2 | 📋 | PatrolLog, PersonProfile |
| `FieldObservations` | 🟠 | P2 | 📋 | PatrolLog |
| `RapidIntervention` | 🟠 | P2 | 📋 | PatrolLog, IncidentCreation |
| `MobileForms` | 🟠 | P2 | 📋 | PatrolLog |
| `PatrolGeolocation` | 🟠 | P2 | 📋 | GpsCoordinates |
| `GpsTracking` | 🟠 | P2 | 📋 | GpsCoordinates |
| `FieldChecklists` | 🟡 | P3 | 📋 | PatrolLog |
| `VoiceNotes` | 🟠 | P3 | 📋 | PatrolLog, Encryption (BLOB) |

**Notes catégorie :** `PersonChecks` est 🔴 car il relie des personnes contrôlées à des localisations et des moments — profilage potentiel.

---

## 6. Enquêtes

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `InvestigationCreation` | 🔴 | P2 | 📋 | IncidentCreation |
| `HypothesisTracking` | 🔴 | P2 | 📋 | InvestigationCreation |
| `CaseLinking` | 🟠 | P2 | 📋 | InvestigationCreation |
| `SuspectTracking` | 🔴 | P2 | 📋 | InvestigationCreation, SuspectManagement |
| `InterviewManagement` | 🔴 | P2 | 📋 | InvestigationCreation, PersonProfile |
| `WarrantManagement` | 🔴 | P2 | 📋 | InvestigationCreation, JudicialAuthorizations |
| `InvestigatorAssignment` | 🟠 | P2 | 📋 | InvestigationCreation, UserManagement |
| `SurveillanceOperations` | 🔴 | P3 | 📋 | InvestigationCreation |
| `SpecialOperations` | 🔴 | P3 | 📋 | InvestigationCreation |
| `InvestigationNotes` | 🔴 | P2 | 📋 | InvestigationCreation, Encryption |
| `DetailedTimeline` | 🟠 | P2 | 📋 | InvestigationCreation |
| `RelationBoards` | 🔴 | P3 | 📋 | InvestigationCreation, LinkAnalysis |
| `InformantManagement` | 🔴 | P3 | 📋 | InvestigationCreation, Encryption |
| `InvestigationPlan` | 🟠 | P2 | 📋 | InvestigationCreation |
| `InternalRequests` | 🟡 | P3 | 📋 | InvestigationCreation |
| `ExternalRequests` | 🟠 | P3 | 📋 | InvestigationCreation |
| `SupervisorValidation` | 🟡 | P2 | 📋 | InvestigationCreation, ApprovalWorkflow |
| `ProsecutorPreparation` | 🔴 | P2 | 📋 | InvestigationCreation, LegalExport |

**Notes catégorie :** Quasi entièrement 🔴. Les enquêtes sont le cœur judiciaire du système. `InformantManagement` est ultra-sensible — la fuite d'un informateur met des vies en danger. Accès restreint, ségrégation des tâches, break-glass uniquement.

---

## 7. Arrestation, détention et garde

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `ArrestRecord` | 🔴 | P2 | 📋 | PersonProfile, IncidentCreation |
| `LegalGrounds` | 🟠 | P2 | 📋 | ArrestRecord |
| `RightsReading` | 🟠 | P2 | 📋 | ArrestRecord |
| `UseOfForce` | 🔴 | P2 | 📋 | ArrestRecord, AuditLog |
| `SearchProcedure` | 🟠 | P2 | 📋 | ArrestRecord |
| `SeizureManagement` | 🟠 | P2 | 📋 | ArrestRecord, EvidenceRegistration |
| `TransportManagement` | 🟠 | P3 | 📋 | ArrestRecord |
| `DetentionManagement` | 🔴 | P2 | 📋 | ArrestRecord |
| `CellOccupancy` | 🟠 | P2 | 📋 | DetentionManagement |
| `PersonalBelongings` | 🟠 | P2 | 📋 | DetentionManagement |
| `VisitorManagement` | 🟠 | P3 | 📋 | DetentionManagement |
| `ReleaseManagement` | 🔴 | P2 | 📋 | DetentionManagement |
| `ConditionalRelease` | 🔴 | P2 | 📋 | ReleaseManagement |
| `ServiceTransfer` | 🟠 | P3 | 📋 | DetentionManagement |
| `CustodyRegister` | 🔴 | P2 | 📋 | DetentionManagement, AuditLog |
| `MedicalCheck` | 🔴 | P2 | 📋 | DetentionManagement |
| `RiskAssessment` | 🔴 | P2 | 📋 | DetentionManagement |
| `CellIncidentTracking` | 🔴 | P2 | 📋 | DetentionManagement, IncidentCreation |

**Notes catégorie :** `UseOfForce`, `MedicalCheck` et `RiskAssessment` sont ultra-critiques — obligations légales de documentation. `RiskAssessment` (suicidaire) doit déclencher des alertes immédiates.

---

## 8. Preuves et chaîne de possession

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `EvidenceRegistration` | 🔴 | P2 | 📋 | IncidentCreation, Encryption |
| `EvidenceCategories` | 🟡 | P2 | 📋 | EvidenceRegistration |
| `EvidenceSeals` | 🟠 | P2 | 📋 | EvidenceRegistration |
| `UniqueEvidenceId` | 🟡 | P2 | 📋 | EvidenceRegistration |
| `EvidencePhotos` | 🔴 | P2 | 📋 | EvidenceRegistration, Encryption (BLOB) |
| `SceneCollection` | 🟠 | P2 | 📋 | EvidenceRegistration, GpsCoordinates |
| `EvidencePackaging` | 🟡 | P3 | 📋 | EvidenceRegistration |
| `EvidenceLocation` | 🟡 | P2 | 📋 | EvidenceRegistration |
| `StorageManagement` | 🟡 | P2 | 📋 | EvidenceRegistration |
| `CustodyTransfer` | 🔴 | P2 | 📋 | EvidenceRegistration, AuditLog |
| `CustodyLog` | 🔴 | P2 | 📋 | EvidenceRegistration, AuditLog |
| `EvidenceCheckInOut` | 🟠 | P2 | 📋 | EvidenceRegistration |
| `EvidenceDestruction` | 🔴 | P3 | 📋 | EvidenceRegistration, AuditLog |
| `EvidenceReturn` | 🟠 | P3 | 📋 | EvidenceRegistration |
| `BiologicalEvidence` | 🔴 | P3 | 📋 | EvidenceRegistration, DnaReference |
| `WeaponsEvidence` | 🔴 | P3 | 📋 | EvidenceRegistration |
| `DrugEvidence` | 🔴 | P3 | 📋 | EvidenceRegistration |
| `SeizedMoney` | 🔴 | P3 | 📋 | EvidenceRegistration |
| `DigitalChainOfCustody` | 🔴 | P2 | 📋 | EvidenceRegistration, Encryption |
| `BarcodeQrTracking` | 🟡 | P3 | 📋 | EvidenceRegistration |
| `EvidenceInventory` | 🟡 | P2 | 📋 | EvidenceRegistration |
| `EvidenceAudit` | 🔴 | P2 | 📋 | EvidenceRegistration, AuditLog |
| `ComplianceIssues` | 🟠 | P3 | 📋 | EvidenceAudit |
| `LabIntegration` | 🟠 | P4 | 📋 | EvidenceRegistration, LaboratoryIntegration |

**Notes catégorie :** La chaîne de possession est juridiquement critique — toute rupture invalide la preuve au tribunal. `CustodyLog` et `CustodyTransfer` doivent être immuables comme `AuditLog`.

---

## 9. Criminalistique et laboratoire

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `LabRequests` | 🟠 | P3 | 📋 | EvidenceRegistration |
| `AnalysisTypes` | 🟡 | P3 | 📋 | LabRequests |
| `DnaAnalysis` | 🔴 | P4 | 📋 | LabRequests, DnaReference |
| `FingerprintAnalysis` | 🔴 | P4 | 📋 | LabRequests, FingerprintMugshot |
| `BallisticsAnalysis` | 🟠 | P4 | 📋 | LabRequests, WeaponsEvidence |
| `ToxicologyAnalysis` | 🔴 | P4 | 📋 | LabRequests |
| `DocumentForensics` | 🟠 | P4 | 📋 | LabRequests |
| `VideoForensics` | 🟠 | P4 | 📋 | LabRequests, Encryption (BLOB) |
| `ExpertReport` | 🟠 | P3 | 📋 | LabRequests, DocumentManagement |
| `LabResults` | 🔴 | P3 | 📋 | LabRequests |
| `SampleComparison` | 🔴 | P4 | 📋 | LabRequests |
| `LabTurnaroundTracking` | 🟡 | P4 | 📋 | LabRequests |
| `AnalysisPrioritization` | 🟡 | P4 | 📋 | LabRequests |
| `ExpertHistory` | 🟠 | P4 | 📋 | LabRequests |

---

## 10. Preuves numériques / cyber

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `DeviceSeizure` | 🔴 | P3 | 📋 | EvidenceRegistration |
| `DigitalInventory` | 🟠 | P3 | 📋 | DeviceSeizure |
| `ForensicImaging` | 🔴 | P3 | 📋 | DeviceSeizure, Encryption (BLOB) |
| `HashIntegrity` | 🟠 | P3 | 📋 | ForensicImaging |
| `PhoneForensics` | 🔴 | P4 | 📋 | DeviceSeizure |
| `ComputerForensics` | 🔴 | P4 | 📋 | DeviceSeizure |
| `RemovableMediaAnalysis` | 🟠 | P4 | 📋 | DeviceSeizure |
| `CloudEvidence` | 🔴 | P4 | 📋 | DeviceSeizure |
| `DataExtraction` | 🔴 | P4 | 📋 | DeviceSeizure, Encryption |
| `ProcessingLog` | 🟠 | P3 | 📋 | DeviceSeizure, AuditLog |
| `SecureStorage` | 🔴 | P3 | 📋 | Encryption (BLOB) |
| `SecureViewing` | 🟠 | P3 | 📋 | SecureStorage |
| `PreliminaryAnalysis` | 🟠 | P4 | 📋 | DeviceSeizure |
| `ControlledExport` | 🔴 | P3 | 📋 | SecureStorage, AuditLog |
| `RedactionTools` | 🟠 | P4 | 📋 | SecureStorage |
| `TechnicalRequests` | 🟡 | P4 | 📋 | DeviceSeizure |
| `CyberInvestigationReport` | 🔴 | P4 | 📋 | DeviceSeizure, DocumentManagement |

---

## 11. Véhicules

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `VehicleProfile` | 🟠 | P2 | 📋 | Core |
| `LicensePlateTracking` | 🟠 | P2 | 📋 | VehicleProfile |
| `VinTracking` | 🟠 | P2 | 📋 | VehicleProfile |
| `VehicleOwnership` | 🔴 | P2 | 📋 | VehicleProfile, PersonProfile |
| `VehicleHistory` | 🟠 | P2 | 📋 | VehicleProfile |
| `StolenVehicles` | 🟠 | P2 | 📋 | VehicleProfile |
| `SeizedVehicles` | 🟠 | P3 | 📋 | VehicleProfile, SeizureManagement |
| `TowingManagement` | 🟡 | P3 | 📋 | VehicleProfile |
| `ImpoundManagement` | 🟡 | P3 | 📋 | VehicleProfile |
| `VehicleInspection` | 🟡 | P3 | 📋 | VehicleProfile |
| `CollisionTracking` | 🟠 | P3 | 📋 | VehicleProfile, IncidentCreation |
| `VehicleSearch` | 🟡 | P2 | 📋 | VehicleProfile |
| `VehiclePersonLinking` | 🔴 | P2 | 📋 | VehicleProfile, PersonProfile |

---

## 12. Biens / objets

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `LostItems` | 🟡 | P3 | 📋 | Core |
| `FoundItems` | 🟡 | P3 | 📋 | Core |
| `StolenItems` | 🟠 | P2 | 📋 | IncidentCreation |
| `SeizedItems` | 🟠 | P2 | 📋 | EvidenceRegistration |
| `ItemReturn` | 🟡 | P3 | 📋 | Core |
| `ItemInventory` | 🟡 | P3 | 📋 | Core |
| `ItemCategorization` | 🟢 | P3 | 📋 | Core |
| `SerialNumberTracking` | 🟡 | P3 | 📋 | Core |
| `ItemPhotos` | 🟠 | P3 | 📋 | Encryption (BLOB) |
| `EstimatedValue` | 🟡 | P3 | 📋 | Core |
| `ItemInvestigationStatus` | 🟡 | P3 | 📋 | IncidentCreation |
| `ItemCaseLinking` | 🟡 | P3 | 📋 | IncidentCreation |

---

## 13. Tribunal et procédures

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `SearchWarrants` | 🔴 | P2 | 📋 | InvestigationCreation |
| `ArrestWarrants` | 🔴 | P2 | 📋 | InvestigationCreation |
| `JudicialAuthorizations` | 🔴 | P2 | 📋 | InvestigationCreation |
| `SummonsManagement` | 🟠 | P3 | 📋 | PersonProfile |
| `CourtNotices` | 🟠 | P3 | 📋 | PersonProfile |
| `CourtHearings` | 🟠 | P3 | 📋 | InvestigationCreation |
| `CourtCalendar` | 🟡 | P3 | 📋 | CourtHearings |
| `CasePreparation` | 🔴 | P2 | 📋 | InvestigationCreation, LegalExport |
| `ProsecutorSubmission` | 🔴 | P2 | 📋 | CasePreparation |
| `ConditionTracking` | 🔴 | P2 | 📋 | ConditionalRelease |
| `CourtEvidence` | 🔴 | P2 | 📋 | EvidenceRegistration |
| `CourtAppearanceHistory` | 🟠 | P3 | 📋 | CourtHearings |
| `CourtOutcomes` | 🔴 | P3 | 📋 | CourtHearings |
| `CourtOrders` | 🔴 | P3 | 📋 | CourtHearings |
| `PostJudgmentTracking` | 🔴 | P3 | 📋 | CourtOutcomes |

---

## 14. Conformité et audit

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `ImmutableAuditLog` | 🟠 | P0 | 📋 | AuditLog (alias) |
| `AccessReview` | 🟡 | P2 | 📋 | AuditLog, UserManagement |
| `DualApproval` | 🟡 | P2 | 📋 | ApprovalWorkflow |
| `RetentionPolicy` | 🟡 | P2 | 📋 | ArchivalRetention |
| `LegalPurge` | 🔴 | P3 | 📋 | AuditLog, ArchivalRetention |
| `LegalHold` | 🔴 | P2 | 📋 | AuditLog |
| `ConsentManagement` | 🔴 | P2 | 📋 | PersonProfile |
| `AccessLog` | 🟠 | P0 | 📋 | AuditLog (alias) |
| `AbuseDetection` | 🟡 | P3 | 📋 | AuditLog, AnomalyDetection |
| `SegregationOfDuties` | 🟡 | P2 | 📋 | RolePermissionManagement |
| `SecurityIncidents` | 🔴 | P2 | 📋 | AuditLog, IncidentCreation |
| `InternalCompliance` | 🟡 | P3 | 📋 | AuditLog |
| `AuditReports` | 🟡 | P3 | 📋 | AuditLog |
| `QualityControl` | 🟡 | P3 | 📋 | Core |
| `FormVersioning` | 🟡 | P3 | 📋 | Core |
| `DocumentGovernance` | 🟡 | P3 | 📋 | DocumentManagement |

---

## 15. Sécurité applicative et IAM

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `SingleSignOn` | 🟠 | P1 | 📋 | AuthIam |
| `MultiFactorAuthentication` | 🟠 | P1 | 📋 | AuthIam |
| `SessionManagement` | 🟠 | P1 | 📋 | AuthIam |
| `ApprovedDevices` | 🟡 | P2 | 📋 | AuthIam |
| `DeviceManagement` | 🟡 | P2 | 📋 | AuthIam |
| `NetworkAccessControl` | 🟡 | P2 | 📋 | AuthIam |
| `DataEncryption` | 🔴 | P0 | 📋 | Encryption (alias) |
| `SecretVault` | 🔴 | P0 | 📋 | Encryption |
| `DigitalSignatureTimestamping` | 🟠 | P3 | 📋 | Encryption |
| `GranularPermissions` | 🟡 | P1 | 📋 | RolePermissionManagement |
| `EmergencyAccess` | 🔴 | P2 | 📋 | AuthIam, AuditLog |
| `AccountReview` | 🟡 | P3 | 📋 | UserManagement |
| `AnomalyDetection` | 🟡 | P3 | 📋 | AuditLog |
| `DataClassification` | 🟡 | P1 | 📋 | Core |
| `SecurityLogging` | 🟠 | P0 | 📋 | AuditLog (alias) |
| `PasswordPolicy` | 🟡 | P1 | 📋 | AuthIam |

---

## 16. Mobile et hors ligne

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `MobileAgentApp` | 🟠 | P3 | 📋 | Core, AuthIam |
| `DeferredSynchronization` | 🟠 | P3 | 📋 | MobileAgentApp |
| `OfflineMode` | 🟠 | P3 | 📋 | MobileAgentApp |
| `SecureCache` | 🔴 | P3 | 📋 | MobileAgentApp, Encryption |
| `MediaCapture` | 🟠 | P3 | 📋 | MobileAgentApp, Encryption (BLOB) |
| `FieldSignature` | 🟠 | P3 | 📋 | MobileAgentApp |
| `QrBarcodeScanning` | 🟡 | P3 | 📋 | MobileAgentApp |
| `LocationTracking` | 🟠 | P3 | 📋 | MobileAgentApp, GpsCoordinates |
| `QuickForms` | 🟡 | P3 | 📋 | MobileAgentApp |
| `PushAlerts` | 🟡 | P3 | 📋 | NotificationSystem |
| `PanicButton` | 🟠 | P3 | 📋 | MobileAgentApp, DispatchSystem |
| `EncryptedLocalValidation` | 🔴 | P3 | 📋 | MobileAgentApp, Encryption |
| `SynchronizationProof` | 🟠 | P3 | 📋 | MobileAgentApp, AuditLog |

---

## 17. Cartographie et géospatial

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `EventMap` | 🟡 | P3 | 📋 | IncidentCreation, GpsCoordinates |
| `HeatmapAnalytics` | 🟡 | P4 | 📋 | EventMap |
| `PatrolZones` | 🟡 | P3 | 📋 | GpsCoordinates |
| `SectorsDistricts` | 🟢 | P2 | 📋 | Core |
| `Geofencing` | 🟡 | P4 | 📋 | GpsCoordinates |
| `HotspotDetection` | 🟡 | P4 | 📋 | EventMap |
| `RouteManagement` | 🟡 | P3 | 📋 | GpsCoordinates |
| `SpatialHistory` | 🟠 | P4 | 📋 | GpsCoordinates, AuditLog |
| `ZoneAnalysis` | 🟡 | P4 | 📋 | GpsCoordinates |
| `SensitiveLocations` | 🟠 | P3 | 📋 | GpsCoordinates |
| `CameraSensorIntegration` | 🟡 | P4 | 📋 | GpsCoordinates |
| `GisIntegration` | 🟡 | P4 | 📋 | GpsCoordinates |
| `AddressNormalization` | 🟢 | P2 | 📋 | Core |
| `GpsCoordinates` | 🟠 | P2 | 📋 | Core |
| `ReverseGeocoding` | 🟢 | P3 | 📋 | GpsCoordinates |

---

## 18. Communications

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `InternalMessaging` | 🟠 | P2 | 📋 | UserManagement |
| `AlertBroadcasting` | 🟡 | P2 | 📋 | NotificationSystem |
| `RadioLogging` | 🟠 | P3 | 📋 | AuditLog |
| `SmsMessaging` | 🟡 | P3 | 📋 | NotificationSystem |
| `EmailMessaging` | 🟡 | P2 | 📋 | NotificationSystem |
| `PushNotifications` | 🟡 | P2 | 📋 | NotificationSystem |
| `MessageTemplates` | 🟢 | P3 | 📋 | Core |
| `CommunicationEscalation` | 🟡 | P3 | 📋 | NotificationSystem |
| `GroupMessaging` | 🟠 | P3 | 📋 | InternalMessaging |
| `BoloApbAlerts` | 🔴 | P2 | 📋 | PersonProfile, AlertBroadcasting |
| `CriticalNotifications` | 🟡 | P2 | 📋 | NotificationSystem |
| `CommunicationHistory` | 🟠 | P3 | 📋 | AuditLog |

**Notes catégorie :** `BoloApbAlerts` (Be On the Lookout / All-Points Bulletin) est 🔴 car il diffuse des données de personnes recherchées.

---

## 19. Intelligence, renseignement et analyse

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `PersonsOfInterest` | 🔴 | P3 | 📋 | PersonProfile |
| `IntelligenceProfiles` | 🔴 | P3 | 📋 | PersonsOfInterest |
| `RelationshipNetworks` | 🔴 | P3 | 📋 | PersonProfile, LinkAnalysis |
| `LinkAnalysis` | 🔴 | P3 | 📋 | PersonProfile |
| `EntityOrganizationManagement` | 🟠 | P3 | 📋 | PersonProfile |
| `OperationalIntelligence` | 🔴 | P3 | 📋 | IntelligenceProfiles |
| `RiskScoring` | 🔴 | P4 | 📋 | PersonProfile |
| `PatternDetection` | 🟠 | P4 | 📋 | IncidentCreation |
| `MultiIncidentCorrelation` | 🟠 | P4 | 📋 | IncidentCreation |
| `CrimeMapping` | 🟡 | P4 | 📋 | EventMap |
| `EventProfiling` | 🟠 | P4 | 📋 | IncidentCreation |
| `PredictiveAlerts` | 🟠 | P4 | 📋 | PatternDetection |
| `RestrictedSensitiveCases` | 🔴 | P2 | 📋 | InvestigationCreation, EmergencyAccess |

**Notes catégorie :** Catégorie la plus sensible du système après la biométrie. `RestrictedSensitiveCases` nécessite un accès ultra-restreint avec break-glass et double approbation.

---

## 20. Statistiques, BI et rapports

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `ExecutiveDashboard` | 🟡 | P3 | 📋 | Core |
| `OperationalKpis` | 🟡 | P3 | 📋 | Core |
| `ResponseTimeAnalytics` | 🟡 | P3 | 📋 | DispatchSystem |
| `ResolutionRateAnalytics` | 🟡 | P3 | 📋 | IncidentCreation |
| `IncidentVolumeAnalytics` | 🟡 | P3 | 📋 | IncidentCreation |
| `AgentWorkloadAnalytics` | 🟡 | P3 | 📋 | UserManagement |
| `ThermalMaps` | 🟡 | P4 | 📋 | EventMap |
| `ScheduledReports` | 🟡 | P3 | 📋 | Core |
| `DataExport` | 🟠 | P2 | 📋 | Core, AuditLog |
| `AnonymousReports` | 🟢 | P3 | 📋 | Core |
| `RegulatoryReports` | 🟡 | P3 | 📋 | Core |
| `DecisionSupportReports` | 🟡 | P4 | 📋 | Core |
| `AnalyticalDataWarehouse` | 🟡 | P4 | 📋 | Core |
| `MultiAgencyBusinessIntelligence` | 🟡 | P4 | 📋 | MultiOrganization |

**Notes catégorie :** Principalement 🟡 car les données sont agrégées/anonymisées. `DataExport` est 🟠 car l'export peut contenir des données déchiffrées.

---

## 21. RH opérationnelles et administration des agents

| Module | Classification | Priorité | Statut | Dépendances |
|---|---|---|---|---|
| `AgentProfile` | 🔴 | P1 | 📋 | UserManagement, Encryption |
| `BadgeNumberManagement` | 🟠 | P1 | 📋 | AgentProfile |
| `AssignmentManagement` | 🟡 | P2 | 📋 | AgentProfile |
| `UnitManagement` | 🟡 | P2 | 📋 | Core |
| `ScheduleManagement` | 🟡 | P2 | 📋 | AgentProfile |
| `LeaveManagement` | 🟡 | P3 | 📋 | AgentProfile |
| `AttendanceTracking` | 🟡 | P2 | 📋 | AgentProfile |
| `OvertimeManagement` | 🟡 | P3 | 📋 | AgentProfile |
| `CertificationManagement` | 🟡 | P2 | 📋 | AgentProfile |
| `TrainingRecords` | 🟡 | P2 | 📋 | AgentProfile |
| `ClearanceManagement` | 🔴 | P2 | 📋 | AgentProfile |
| `IssuedEquipment` | 🟡 | P3 | 📋 | AgentProfile |
| `DisciplinaryActions` | 🔴 | P3 | 📋 | AgentProfile, AuditLog |
| `PerformanceEvaluations` | 🟠 | P3 | 📋 | AgentProfile |
| `WorkplaceIncidents` | 🟠 | P3 | 📋 | AgentProfile |
| `WellnessSupport` | 🔴 | P3 | 📋 | AgentProfile |
| `CredentialExpirationTracking` | 🟡 | P2 | 📋 | AgentProfile |

**Notes catégorie :** `WellnessSupport` (soutien psychologique) est 🔴 — données médicales protégées. `DisciplinaryActions` et `ClearanceManagement` aussi pour raisons évidentes.

---

## 22–32. Catégories restantes (résumé)

### 22. Formation et qualification — Majorité 🟡

| Module | Classification | Priorité |
|---|---|---|
| `TrainingCatalog` | 🟢 | P3 |
| `MandatoryLearningPaths` | 🟡 | P3 |
| `WeaponCertifications` | 🟠 | P2 |
| `RecertificationManagement` | 🟡 | P3 |
| `TrainingSimulations` | 🟡 | P4 |
| `AssessmentManagement` | 🟡 | P3 |
| `TrainingHistory` | 🟡 | P3 |
| `ValidityExpirationTracking` | 🟡 | P3 |
| `ElearningPlatform` | 🟡 | P4 |
| `FieldAccreditation` | 🟡 | P3 |
| `AutomaticAssignment` | 🟡 | P4 |

### 23. Armurerie et équipement — Majorité 🟡-🟠

| Module | Classification | Priorité |
|---|---|---|
| `WeaponInventory` | 🟠 | P2 |
| `AmmunitionManagement` | 🟠 | P3 |
| `VestManagement` | 🟡 | P3 |
| `RadioEquipment` | 🟡 | P3 |
| `BodycamInventory` | 🟡 | P3 |
| `ServiceVehicles` | 🟡 | P3 |
| `EquipmentAssignment` | 🟡 | P2 |
| `EquipmentMaintenance` | 🟡 | P3 |
| `EquipmentReplacement` | 🟡 | P4 |
| `PeriodicInspection` | 🟡 | P3 |
| `UsageHistory` | 🟡 | P3 |
| `LossDamageTheftTracking` | 🟠 | P3 |
| `EquipmentReservations` | 🟡 | P4 |

### 24. Bodycam, médias et vidéos — Majorité 🔴

| Module | Classification | Priorité |
|---|---|---|
| `VideoImport` | 🔴 | P3 |
| `IncidentMediaLinking` | 🟠 | P3 |
| `MediaTimestamping` | 🟡 | P3 |
| `MediaRetention` | 🟠 | P3 |
| `MediaRedactionBlur` | 🔴 | P3 |
| `ControlledMediaSharing` | 🔴 | P3 |
| `SecureMediaViewing` | 🔴 | P3 |
| `MediaClips` | 🟠 | P3 |
| `MediaChainOfCustody` | 🔴 | P3 |
| `MediaAccessLog` | 🟠 | P3 |
| `MediaRetentionPolicy` | 🟡 | P4 |
| `IntegritySignature` | 🟠 | P3 |

### 25. Portail citoyen / externe — Majorité 🟠

| Module | Classification | Priorité |
|---|---|---|
| `ComplaintSubmission` | 🟠 | P3 |
| `PublicCaseTracking` | 🟡 | P3 |
| `DocumentRequests` | 🟡 | P3 |
| `LostPropertyReport` | 🟡 | P4 |
| `MinorIncidentReporting` | 🟠 | P3 |
| `AppointmentScheduling` | 🟡 | P4 |
| `CitizenPayments` | 🔴 | P4 |
| `SecureFileUpload` | 🟠 | P3 |
| `CitizenNotifications` | 🟡 | P3 |
| `IdentityVerification` | 🔴 | P3 |
| `PublicForms` | 🟡 | P4 |
| `PublicKnowledgeBase` | 🟢 | P4 |

### 26. Workflow, BPM — Majorité 🟡

| Module | Classification | Priorité |
|---|---|---|
| `WorkflowEngine` | 🟡 | P2 |
| `ApprovalManagement` | 🟡 | P2 |
| `EscalationManagement` | 🟡 | P2 |
| `SlaManagement` | 🟡 | P3 |
| `ProcessAutomation` | 🟡 | P3 |
| `BusinessRulesEngine` | 🟡 | P3 |
| `AutomatedTasks` | 🟡 | P3 |
| `ProcedureChecklists` | 🟡 | P3 |
| `ProcessTemplates` | 🟢 | P3 |
| `InterModuleOrchestration` | 🟡 | P2 |

### 27. Intégrations — Majorité 🟡-🟠

| Module | Classification | Priorité |
|---|---|---|
| `ApiIntegrationLayer` | 🟡 | P2 |
| `EventBus` | 🟡 | P1 |
| `BatchImportExport` | 🟠 | P3 |
| `NiemDataExchange` | 🟠 | P4 |
| `JudicialSystemIntegration` | 🔴 | P3 |
| `LaboratoryIntegration` | 🟠 | P4 |
| `TelephonyIntegration` | 🟡 | P4 |
| `MessagingIntegration` | 🟡 | P2 |
| `MappingIntegration` | 🟡 | P3 |
| `DirectoryIntegration` | 🟠 | P1 |
| `BiometricIntegration` | 🔴 | P4 |
| `ObjectStorageIntegration` | 🟡 | P3 |
| `SiemIntegration` | 🟡 | P3 |
| `DigitalSignatureIntegration` | 🟠 | P3 |
| `ErpHrIntegration` | 🟡 | P4 |
| `PaymentGatewayIntegration` | 🔴 | P4 |
| `DocumentAiOcrIntegration` | 🟡 | P4 |

### 28. Qualité des données — Majorité 🟡

| Module | Classification | Priorité |
|---|---|---|
| `DataDeduplication` | 🟡 | P3 |
| `BusinessValidation` | 🟡 | P2 |
| `MasterReferenceData` | 🟢 | P1 |
| `StandardizedAddresses` | 🟢 | P2 |
| `ConsistencyChecks` | 🟡 | P3 |
| `DuplicateDetection` | 🟡 | P3 |
| `CompletenessRules` | 🟡 | P3 |
| `CorrectionQueue` | 🟡 | P3 |
| `ChangeLog` | 🟡 | P2 |
| `SupervisorApproval` | 🟡 | P2 |

### 29. IA et assistance opérationnelle — Majorité 🟠

| Module | Classification | Priorité |
|---|---|---|
| `AutomaticReportSummary` | 🟠 | P4 |
| `IncidentClassificationSuggestion` | 🟡 | P4 |
| `EntityExtraction` | 🟠 | P4 |
| `AudioTranscription` | 🔴 | P4 |
| `SemanticSearch` | 🟡 | P4 |
| `DuplicateDetectionAi` | 🟡 | P4 |
| `CaseLinkSuggestion` | 🟠 | P4 |
| `WritingAssistance` | 🟡 | P4 |
| `InternalTranslation` | 🟡 | P4 |
| `AssistedRedaction` | 🟠 | P4 |
| `VisualSearch` | 🟠 | P4 |
| `IntelligentAlerts` | 🟡 | P4 |

### 30. Modules spécialisés — Majorité 🔴

| Module | Classification | Priorité |
|---|---|---|
| `DomesticViolence` | 🔴 | P3 |
| `YouthJuvenileCases` | 🔴 | P3 |
| `NarcoticsManagement` | 🔴 | P3 |
| `FinancialCrimes` | 🔴 | P4 |
| `CyberCrimes` | 🔴 | P4 |
| `HomicideInvestigations` | 🔴 | P3 |
| `MissingPersonsCases` | 🔴 | P2 |
| `ExploitationTrafficking` | 🔴 | P3 |
| `RoadSafety` | 🟡 | P3 |
| `AnimalControl` | 🟡 | P4 |
| `FireDisasterResponse` | 🟡 | P4 |
| `EmergencyManagement` | 🟠 | P3 |
| `MunicipalPublicSafety` | 🟡 | P3 |
| `CommunityPolicing` | 🟡 | P4 |
| `MajorEventsManagement` | 🟠 | P3 |
| `CrowdOrderManagement` | 🟠 | P3 |
| `TransportSecurity` | 🟡 | P4 |
| `BorderControl` | 🔴 | P4 |
| `CorrectionalIntelligence` | 🔴 | P4 |
| `K9UnitManagement` | 🟡 | P4 |
| `CrimeSceneManagement` | 🔴 | P3 |

**Notes catégorie :** `YouthJuvenileCases` est ultra-sensible — les données de mineurs ont des protections légales supplémentaires (LSJPA au Québec). Accès restreint, ségrégation obligatoire.

### 31. Modules SaaS / MSP internes — Majorité 🟡

| Module | Classification | Priorité |
|---|---|---|
| `TenantManagement` | 🟡 | P0 |
| `BillingManagement` | 🔴 | P2 |
| `PlanLicenseManagement` | 🟡 | P2 |
| `FeatureFlagManagement` | 🟢 | P2 |
| `ClientBranding` | 🟢 | P3 |
| `UsageLimits` | 🟡 | P2 |
| `AutomaticProvisioning` | 🟡 | P2 |
| `IntegratedSupport` | 🟡 | P3 |
| `HelpCenter` | 🟢 | P3 |
| `ProductTelemetry` | 🟡 | P3 |
| `PlatformHealth` | 🟡 | P1 |
| `TenantMigrations` | 🟡 | P2 |
| `TenantBackups` | 🔴 | P1 |

### 32. Modules techniques Laravel — Majorité 🟡

| Module | Classification | Priorité |
|---|---|---|
| `Core` | 🟡 | P0 |
| `AuthIam` | 🔴 | P0 |
| `Audit` | 🟠 | P0 |
| `Media` | 🟠 | P1 |
| `Workflow` | 🟡 | P2 |
| `Search` | 🟡 | P2 |
| `Notifications` | 🟡 | P1 |
| `Reporting` | 🟡 | P2 |
| `Gis` | 🟡 | P3 |
| `Evidence` | 🔴 | P2 |
| `Incident` | 🟠 | P1 |
| `CaseInvestigation` | 🔴 | P2 |
| `Person` | 🔴 | P1 |
| `Vehicle` | 🟠 | P2 |
| `Court` | 🔴 | P2 |
| `Integration` | 🟡 | P2 |
| `MobileSync` | 🟠 | P3 |
| `Settings` | 🟡 | P1 |
| `Tenant` | 🟡 | P0 |

---

## Résumé statistique

| Classification | Nombre de modules | Pourcentage |
|---|---|---|
| 🔴 CRITIQUE | ~125 | ~35% |
| 🟠 SENSIBLE | ~115 | ~33% |
| 🟡 INTERNE | ~100 | ~28% |
| 🟢 PUBLIC | ~15 | ~4% |

**Constat :** 68% des modules sont 🔴 ou 🟠, ce qui confirme que l'architecture de chiffrement mise en place est indispensable. Le module `Encryption` est une dépendance de fait pour les deux tiers du système.

---

## Ordre d'implémentation recommandé

### Phase 0 — Fondation (P0)
`Core` → `Tenant` → `AuthIam` → `Encryption` → `Audit` → `UserManagement` → `RolePermissionManagement` → `QueueScheduler` → `GlobalSettings` → `MultiOrganization`

### Phase 1 — Noyau métier (P1)
`PersonProfile` → `AgentProfile` → `IncidentCreation` → `DocumentManagement` → `NotificationSystem` → `EventBus` → `MasterReferenceData` → `DirectoryIntegration`

### Phase 2 — Opérations (P2)
`InvestigationCreation` → `ArrestRecord` → `EvidenceRegistration` → `VehicleProfile` → `WorkflowEngine` → `DataExport` → `SearchWarrants` → `RestrictedSensitiveCases`

### Phase 3+ — Extensions
Toutes les catégories restantes par priorité décroissante.
