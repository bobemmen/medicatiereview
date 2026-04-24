# Medicatiereview — Claude instructies

## Git workflow

- Ontwikkel op de toegewezen feature branch (bijv. `claude/...`).
- Merge na het pushen van de feature branch **direct** via fast-forward in de originele branch (`claude/patient-record-webapp-plan-ASxVm`) en push die ook.
- Gebruik altijd `git merge --ff-only` zodat de history lineair blijft.
