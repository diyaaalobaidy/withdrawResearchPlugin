# Author Research Withdrawal Plugin for OJS 3.5

An enterprise-ready **Open Journal Systems (OJS 3.5)** generic plugin that empowers authors to formally request and withdraw their research manuscripts from the journal editorial workflow, with customizable policies, reason tracking, activity audit logging, and automated notifications to editors.

---

## Features

- **Author Self-Service Withdrawal**: Assigned authors or submitters can withdraw their manuscript directly from the submission workflow view.
- **Strict Role & Stage Permissions**:
  - Only authorized authors of the manuscript can initiate withdrawal.
  - Journal managers can configure which workflow stages allow withdrawal (Submission, External Review, Editorial, Production).
  - Published articles are automatically protected from author withdrawal.
- **Customizable Reasons & Explanations**:
  - Standard withdrawal reasons (duplicate submission, data/finding error, authorship dispute, alternate venue, institutional/funder embargo, excessive review delay, or other).
  - Configurable option to require reason selection and optional detailed explanation.
- **Journal Policy Display**: Journal managers can specify withdrawal terms and conditions displayed directly to the author in the withdrawal modal dialog.
- **Author Co-Author Confirmation**: Author must check an explicit confirmation box certifying authorization on behalf of all co-authors.
- **Automated Email Notifications**:
  - Alerts assigned Section Editors and Journal Managers immediately upon withdrawal with the author's stated reason and notes.
  - Sends a formal confirmation receipt to the withdrawing author.
  - Compatible with OJS 3.5 / Laravel Mail facade (`PKP\mail\Mailable` and `Mail::send`).
- **Audit Logging**: Logs the withdrawal event, user, timestamp, reason, and notes to the OJS submission activity log.
- **Bilingual Support**: Fully localized in English (`en_US`, `en`) and Arabic (`ar`, `ar_IQ`).

---

## Installation

1. Copy or clone the `withdrawResearchPlugin` folder into your OJS installation under:
   ```bash
   plugins/generic/withdrawResearchPlugin
   ```
2. Navigate to your OJS administration dashboard:
   **Settings > Website > Plugins > Installed Plugins**
3. Locate **Author Research Withdrawal Plugin** under Generic Plugins and check the box to **Enable** it.
4. Click the expanded arrow next to the plugin name and select **Settings** to configure allowed stages, notifications, and withdrawal policies.

---

## Directory Structure

```text
withdrawResearchPlugin/
├── classes/
│   ├── handlers/
│   │   ├── AuthorWithdrawHandler.php     # Author modal & submission action controller
│   │   └── WithdrawSettingsHandler.php   # Journal manager settings controller
│   ├── mail/
│   │   └── WithdrawalMailable.php        # OJS 3.5 Mailable email handler
│   └── WithdrawalService.php             # Core withdrawal business logic & validation
├── css/
│   └── withdrawResearch.css              # Frontend & modal styles
├── js/
│   └── withdrawResearch.js               # Frontend workflow injection script
├── locale/
│   ├── ar/locale.po                      # Arabic translation
│   ├── ar_IQ/locale.po                   # Iraqi Arabic translation
│   ├── en/locale.po                      # English translation
│   └── en_US/locale.po                   # English (US) translation
├── templates/
│   ├── author/
│   │   └── withdrawModal.tpl             # Author withdrawal confirmation modal
│   └── settings/
│       └── settings.tpl                  # Plugin settings modal
├── index.php                             # Plugin bootstrap loader
├── version.xml                           # Plugin metadata and version manifest
├── LICENSE                               # GNU General Public License v3
└── README.md                             # Documentation & user guide
```

---

## Configuration Options

In **Settings > Website > Plugins > Author Research Withdrawal Plugin > Settings**:

| Setting | Default | Description |
|---|---|---|
| **Enable Research Withdrawal** | Checked | Turns author withdrawal on/off for the journal. |
| **Require Author Reason** | Checked | Requires authors to select/specify a reason prior to withdrawal. |
| **Permitted Workflow Stages** | All Stages | Choose which stages allow withdrawal (Submission, Review, Copyediting, Production). |
| **Notify Assigned Editors** | Checked | Automatically sends email notification with withdrawal details to editors. |
| **Send Confirmation to Author** | Checked | Sends email receipt to the withdrawing author. |
| **Journal Withdrawal Policy** | Empty | Text displayed to authors in the withdrawal modal dialog. |

---

## License

Distributed under the GNU General Public License version 3 (GPLv3).
