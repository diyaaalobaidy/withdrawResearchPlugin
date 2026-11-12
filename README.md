# Author Research Withdrawal Plugin for OJS 3.5
# إضافة سحب البحوث للمؤلف في نظام المجلات المفتوحة (OJS 3.5)

An enterprise-ready **Open Journal Systems (OJS 3.5)** generic plugin that empowers authors to formally request and withdraw their research manuscripts from the editorial workflow with customizable policies, reason tracking, activity audit logging, and automated notifications to editors.

إضافة متوافقة مع نظام إدارة المجلات المفتوحة **OJS 3.5** تتيح للمؤلفين سحب بحوثهم ومقالاتهم العلمية رسمياً من مسار التحرير، مع تتبع الأسباب وتسجيل النشاط في سجل التدقيق وإرسال إشعارات بريدية آلية لرؤساء التحرير والمحررين المسؤولين مع إمكانية تحديد سياسة السحب الخاصة بالمجلة.

---

## Table of Contents / فهرس المحتويات
- [English Documentation](#english-documentation)
  - [Features](#features)
  - [System Requirements](#system-requirements)
  - [Installation](#installation)
  - [Configuration Guide (Journal Managers)](#configuration-guide-journal-managers)
  - [Author Usage Instructions (Step-by-Step)](#author-usage-instructions-step-by-step)
  - [Troubleshooting & FAQ](#troubleshooting--faq)
  - [Architecture & Directory Structure](#architecture--directory-structure)
- [دليل الاستخدام باللغة العربية](#دليل-الاستخدام-باللغة-العربية)
  - [المميزات والخصائص](#المميزات-والخصائص)
  - [متطلبات النظام](#متطلبات-النظام)
  - [طريقة التثبيت](#طريقة-التثبيت)
  - [دليل الإعداد والضبط (مدراء المجلة)](#دليل-الإعداد-والضبط-مدراء-المجلة)
  - [دليل الاستخدام للمؤلف (خطوة بخطوة)](#دليل-الاستخدام-للمؤلف-خطوة-بخطوة)
  - [الأسئلة الشائعة واستكشاف الأخطاء](#الأسئلة-الشائعة-واستكشاف-الأخطاء)
  - [هيكل الملفات والبرمجة](#هيكل-الملفات-والبرمجة)
- [License / الترخيص](#license--الترخيص)

---

# English Documentation

## Features

- **Strict Role-Based Access Control**:
  - The withdrawal button is visible and accessible **strictly to authorized authors and submitters** of the manuscript.
  - Editors, Section Editors, and Journal Managers cannot see the withdrawal button on author manuscripts unless they are the actual author of that paper.
- **Workflow Stage Control**: Journal managers configure which stages permit withdrawal (Submission, External Review, Copyediting, Production).
- **Publication Safety Guard**: Published papers (`STATUS_PUBLISHED`) and already declined manuscripts are strictly protected from withdrawal.
- **Dedicated UI Placement**: The "Withdraw Research" action button is placed in the top action header bar next to the "Submission Library" button.
- **Detailed Reason Tracking & Policy Notice**:
  - Standard withdrawal reasons (Data/Methodological errors, Duplicate submission, Authorship disputes, Submission to alternative venue, Funder/Institutional embargo, Excessive review delay, or Other).
  - Customizable journal policy box displayed inside the withdrawal dialog.
  - Mandatory co-author authorization confirmation checkbox.
- **Automated Bilingual Email Notifications**:
  - Automatically notifies assigned Section Editors and Journal Managers with the manuscript title, ID, author name, withdrawal reason, and comments.
  - Automatically sends a confirmation receipt to the withdrawing author.
  - Built natively for OJS 3.5 using `PKP\mail\Mailable` and the Laravel Mail architecture.
- **Full Audit Logging**: Logs the withdrawal event, timestamp, user ID, reason, and comments to the OJS submission activity log.
- **Bilingual Interface**: Full native localization in English (`en_US`, `en`) and Arabic (`ar`, `ar_IQ`).

---

## System Requirements

- **OJS Version**: Open Journal Systems 3.5.0-x
- **PHP Version**: PHP 8.1, 8.2, 8.3, or 8.4
- **Database**: MySQL 8.0+ / MariaDB 10.5+ / PostgreSQL 13+

---

## Installation

### Method 1: Git Clone
1. Navigate to your OJS plugins generic directory:
   ```bash
   cd /var/www/html/ojs/plugins/generic/
   ```
2. Clone the repository:
   ```bash
   git clone https://github.com/diyaaalobaidy/withdrawResearchPlugin.git withdrawResearchPlugin
   ```
3. Set appropriate permissions:
   ```bash
   chown -R www-data:www-data withdrawResearchPlugin
   chmod -R 755 withdrawResearchPlugin
   ```
4. Clear OJS template and file cache:
   ```bash
   rm -rf /var/www/html/ojs/cache/t_compile/* /var/www/html/ojs/cache/*.php
   ```

### Method 2: Manual Upload (ZIP)
1. Download or package the plugin into a folder named `withdrawResearchPlugin`.
2. Upload and extract it to:
   ```text
   <ojs_root>/plugins/generic/withdrawResearchPlugin/
   ```
3. Ensure file permissions allow the web server user (`www-data` or `apache`) read access.
4. Clear the cache files in `<ojs_root>/cache/`.

---

## Configuration Guide (Journal Managers)

> **Important**: By default, the plugin is **inactive** upon installation until enabled by the Journal Manager for each specific journal context.

1. Log into OJS as a **Journal Manager** or **Administrator**.
2. Select your journal context from the top navigation dropdown.
3. In the left navigation menu, go to **Settings > Website > Plugins**.
4. In the **Installed Plugins** tab, scroll down to **Generic Plugins** and locate **Author Research Withdrawal Plugin**.
5. Check the box to enable the plugin.
6. Click the arrow icon (▼) next to the plugin name and select **Settings**.
7. Configure the following options:
   - **Enable Research Withdrawal**: Toggle withdrawal availability for this journal.
   - **Require Author Reason**: Require authors to select a withdrawal category before submitting.
   - **Permitted Workflow Stages**: Select the stages where authors may withdraw (e.g. *Submission*, *Review*, *Copyediting*, *Production*).
   - **Notify Assigned Editors**: Send automated emails to assigned Section Editors and Journal Managers when a withdrawal takes place.
   - **Send Confirmation to Author**: Send an email receipt confirming withdrawal to the author.
   - **Journal Withdrawal Policy**: Enter the journal's official withdrawal policies, fees (if any), or conditions shown to the author in the modal dialog.
8. Click **Save Settings**.

---

## Author Usage Instructions (Step-by-Step)

### Step 1: Open Your Submission
1. Log in to your OJS account as the **Author**.
2. In your **Submissions** dashboard (or via `/dashboard/mySubmissions`), locate and open the active manuscript you wish to withdraw.

### Step 2: Locate the "Withdraw Research" Button
- Look at the top-right action header bar next to the **Submission Library** button.
- You will see a red button labeled:
  - English: **Withdraw Research**
  - Arabic: **سحب البحث**
- *(Note: If the button is not visible, check that the plugin is enabled by the journal manager, that you are logged in as the author, and that your article has not already been published or declined).*

### Step 3: Fill Out the Withdrawal Form
1. Click **Withdraw Research**. A modal window will open on top of the workflow screen.
2. Read the **Journal Withdrawal Policy** displayed at the top of the modal.
3. Select a **Reason for Withdrawal** from the dropdown:
   - *Error in data or methodology*
   - *Duplicate submission*
   - *Authorship dispute*
   - *Submission to another journal*
   - *Funder or institutional embargo*
   - *Excessive delay in review process*
   - *Other reason*
4. Enter any additional explanations in the **Detailed Explanation / Notes** text box.
5. Check the confirmation box:
   > *"I confirm that I am authorized by all co-authors to request the withdrawal of this submission, and I understand that this action is final and cannot be undone."*

### Step 4: Confirm Withdrawal
1. Click the red **Confirm Withdrawal** button.
2. The page will reload. The status of the submission will change to **Declined / Withdrawn**.
3. An automated email receipt will be sent to your registered author email address, and the assigned editors will receive formal notification.

---

## Troubleshooting & FAQ

### Why is the "Withdraw Research" button not showing?
Open your browser Developer Tools (F12) -> Console tab and enter:
```javascript
console.log(window.withdrawResearchPluginConfig);
```
Look at the `reasonCode` attribute:
- `plugin_disabled`: The plugin is installed but not enabled in **Settings > Website > Plugins > Author Research Withdrawal Plugin > Settings**.
- `not_author`: The current logged-in account is not an author or submitter on this submission. Editors and managers cannot view or use this button on authors' behalf.
- `already_published`: The manuscript is already published (`STATUS_PUBLISHED = 3`).
- `already_declined_or_withdrawn`: The manuscript is already marked as declined or withdrawn (`STATUS_DECLINED = 4`).
- `stage_not_allowed`: The manuscript is at a workflow stage not selected in the plugin settings.

### Can an editor withdraw a submission on behalf of an author?
No. The plugin is strictly built as an **author self-service** mechanism to avoid unauthorized withdrawals. Editors can decline or unpublish manuscripts using standard OJS editorial controls.

### How do I customize email templates?
The notifications are sent via OJS 3.5's `WithdrawalMailable` class. The email subject and body are localized in `locale/en_US/locale.po` and `locale/ar_IQ/locale.po` under the keys:
- `plugins.generic.withdrawResearchPlugin.mail.authorNotification.subject`
- `plugins.generic.withdrawResearchPlugin.mail.authorNotification.body`
- `plugins.generic.withdrawResearchPlugin.mail.editorNotification.subject`
- `plugins.generic.withdrawResearchPlugin.mail.editorNotification.body`

---

# دليل الاستخدام باللغة العربية

## المميزات والخصائص

- **صلاحيات صارمة وحصرية للمؤلفين**:
  - زر سحب البحث مخصص ومتاح **حصرياً للمؤلفين والمقدمين المعتمدين** للمخطوطة.
  - لا يظهر الزر للمحررين، أو محرري الأقسام، أو مدراء المجلة عند استعراضهم لبحوث المؤلفين، منعاً لأي تداخل بالصلاحيات.
- **التحكم بمراحل النشر**: يمتلك مدير المجلة الصلاحية لتحديد المراحل المسموح فيها بسحب البحث (مرحلة التقديم، التحكيم الخارجي، التحرير، أو الإنتاج).
- **حماية البحوث المنشورة**: يُمنع سحب أي بحث منشور مسبقاً (`STATUS_PUBLISHED`) أو بحث تم رفضه مسبقاً لحفظ سجل الأرشيف.
- **واجهة مدمجة ومتطابقة مع OJS 3.5**: يظهر زر "سحب البحث" في الشريط العلوي بجوار زر "مكتبة التقديم" (Submission Library).
- **توثيق أسباب السحب مع عرض السياسات**:
  - قائمة خيارات جاهزة لأسباب السحب (أخطاء بيانات أو منهجية، تكرار التقديم، نزاع ملكية فكرية، تقديم لمجلة أخرى، تأخر التحكيم، أسباب مؤسسية، أو أسباب أخرى).
  - إمكانية كتابة سياسة وشروط المجلة الخاصة بالسحب لتظهر للمؤلف قبل تأكيد الطلب.
  - مربع تأكيد إلزامي بإقرار موافقة جميع الباحثين المشاركين على طلب السحب.
- **إشعارات بريدية آلية ثنائية اللغة**:
  - إرسال إشعار فوري لمحرري القسم ومدير المجلة يتضمن تفاصيل السحب واسم الباحث والسبب وملاحظاته.
  - إرسال إشعار تأكيد بريدي للمؤلف يوثق عملية السحب.
  - مبني وفق معمارية بريد OJS 3.5 الحديثة (`PKP\mail\Mailable`).
- **سجل تدقيق كامل للنشاط**: تسجيل عملية السحب، واسم المستخدم، والتوقيت، والسبب في سجل أنشطة البحث (Activity Log).
- **دعم كامل للغة العربية والإنجليزية**: واجهة ورسائل ونماذج مترجمة بالكامل (`ar` / `ar_IQ` و `en` / `en_US`).

---

## متطلبات النظام

- **نظام المجلات**: Open Journal Systems (OJS 3.5.0-x)
- **إصدار PHP**: يدعم PHP 8.1, 8.2, 8.3, 8.4
- **قواعد البيانات**: MySQL 8.0+ أو MariaDB 10.5+ أو PostgreSQL 13+

---

## طريقة التثبيت

### الطريقة الأولى: عبر سطر الأوامر (Git)
1. انتقل إلى مجلد الإضافات العامة في نظام OJS:
   ```bash
   cd /var/www/html/ojs/plugins/generic/
   ```
2. استنسخ مستودع الإضافة:
   ```bash
   git clone https://github.com/diyaaalobaidy/withdrawResearchPlugin.git withdrawResearchPlugin
   ```
3. اضبط ملكية وصلاحيات المجلد لمستخدم الويب:
   ```bash
   chown -R www-data:www-data withdrawResearchPlugin
   chmod -R 755 withdrawResearchPlugin
   ```
4. احذف الملفات المؤقتة (الكاش):
   ```bash
   rm -rf /var/www/html/ojs/cache/t_compile/* /var/www/html/ojs/cache/*.php
   ```

### الطريقة الثانية: الرفع اليدوي (ملف مضغوط ZIP)
1. قم بفك ضغط ملفات الإضافة داخل المجلد:
   ```text
   <ojs_root>/plugins/generic/withdrawResearchPlugin/
   ```
2. تأكد من إعطاء صلاحيات القراءة لخادم الويب (`www-data` أو `apache`).
3. امسح ملفات الكاش داخل مجلد `<ojs_root>/cache/`.

---

## دليل الإعداد والضبط (مدراء المجلة)

> **تنبيه هام**: تكون الإضافة **معطلة افتراضياً** عند أول تثبيت لكل مجلة، ويجب تفعيلها من قبل مدير المجلة.

1. سجّل الدخول بحساب **مدير المجلة (Journal Manager)** أو **مسؤول النظام (Admin)**.
2. اختر المجلة المطلوبة من القائمة العلوية المنسدلة.
3. من القائمة الجانبية، اذهب إلى: **الإعدادات > الموقع الإلكتروني > الإضافات** (**Settings > Website > Plugins**).
4. في تبويب **الإضافات المنصبة (Installed Plugins)**، مرر لأسفل إلى قسم **الإضافات العامة (Generic Plugins)** وابحث عن **إضافة سحب البحوث للمؤلف (Author Research Withdrawal Plugin)**.
5. ضع علامة صح لتفعيل الإضافة.
6. انقر على السهم الأزرق بجانب اسم الإضافة، ثم اختر **الإعدادات (Settings)**.
7. اضبط الخيارات التالية بحسب سياسة مجلتك:
   - **تفعيل سحب البحوث**: تفعيل أو تعطيل ميزة السحب في هذه المجلة.
   - **إلزامية تحديد سبب السحب**: إجبار الباحث على اختيار سبب السحب قبل الإرسال.
   - **مراحل مسار العمل المسموح بها**: حدد المراحل التي يُسمح للمؤلف بسحب بحثه خلالها (مثل: التقديم، التحكيم، التدقيق اللغوي، الإنتاج).
   - **إشعار المحررين المكلفين**: إرسال بريد إلكتروني تلقائي لهيئة التحرير عند سحب أي بحث.
   - **إرسال إشعار تأكيد للمؤلف**: إرسال إشعار بريدي للمؤلف يوثق نجاح سحب البحث.
   - **سياسة وشروط سحب البحوث بالمجلة**: اكتب النص والشروط التوجيهية التي تود ظهورها للباحث داخل نافذة السحب.
8. اضغط على **حفظ الإعدادات**.

---

## دليل الاستخدام للمؤلف (خطوة بخطوة)

### الخطوة 1: الدخول إلى البحث
1. سجل الدخول إلى حسابك في المجلة بصفتك **مؤلفاً (Author)**.
2. من لوحة التحكم، اذهب إلى **التقديمات (Submissions / My Submissions)** وافتح البحث النشط المراد سحبه.

### الخطوة 2: تحديد موقع زر "سحب البحث"
- انظر إلى الشريط العلوي للبحث بجوار زر **مكتبة التقديم (Submission Library)**.
- ستشاهد زراً أحمر اللون بعنوان **سحب البحث (Withdraw Research)**.
- *(ملاحظة: إذا لم يظهر الزر، يرجى التأكد من تفعيل الإضافة من قبل المجلة، وأنك مسجل بحساب المؤلف نفسه، وأن البحث غير منشور مسبقاً).*

### الخطوة 3: تعبئة نموذج السحب
1. اضغط على زر **سحب البحث**. ستظهر نافذة منبثقة أعلى الشاشة.
2. اقرأ **سياسة وشروط السحب للمجلة** المعروضة في أعلى النافذة بعناية.
3. اختر **سبب السحب** من القائمة المنسدلة:
   - *خطأ في البيانات أو المنهجية*
   - *تقديم مكرر للبحث*
   - *نزاع حول حقوق التأليف*
   - *رغبة في التقديم لمجلة أخرى*
   - *متطلبات جهة التمويل أو المؤسسة*
   - *تأخر غير مبرر في عملية التحكيم*
   - *أسباب أخرى*
4. اكتب أي تفاصيل إضافية في حقل **شرح تفصيلي / ملاحظات إضافية**.
5. ضع علامة صح في مربع الإقرار والتأكيد:
   > *"أقر بأنني مخول من قبل كافة الباحثين المشاركين بطلب سحب هذا البحث، وأدرك أن هذا الإجراء نهائي ولا يمكن التراجع عنه."*

### الخطوة 4: تأكيد السحب
1. اضغط على الزر الأحمر **تأكيد سحب البحث**.
2. سيتم تحديث الصفحة وتتحول حالة البحث رسمياً إلى **مرفوض / مسحوب (Declined / Withdrawn)**.
3. ستصلك رسالة تأكيد رسمية عبر بريدك الإلكتروني المسجل بالمجلة، كما سيتم إشعار رئيس وهيئة التحرير بالطلب وتفاصيله فوراً.

---

## الأسئلة الشائعة واستكشاف الأخطاء

### لماذا يظهر في وحدة تحكم المتصفح `canWithdraw: false` ولا يظهر الزر؟
افتح أدوات المطور في المتصفح (F12) واكتب في الـ Console:
```javascript
console.log(window.withdrawResearchPluginConfig);
```
تحقق من قيمة `reasonCode`:
- `plugin_disabled`: الإضافة غير مفعلة في إعدادات المجلة الحالية. اذهب إلى **الإعدادات > الموقع > الإضافات** وقم بتفعيلها وحفظ إعداداتها.
- `not_author`: الحساب المسجل به حالياً ليس هو مؤلف البحث أو مقدمه. تم تصميم الإضافة لمنع المحررين من سحب أبحاث الباحثين نيابة عنهم.
- `already_published`: البحث منشور بالفعل، ولا يسمح النظام بسحب الأبحاث بعد نشرها.
- `already_declined_or_withdrawn`: تم سحب البحث أو رفضه مسبقاً.
- `stage_not_allowed`: مرحلة البحث الحالية غير مسموح بالسحب فيها وفقاً للإعدادات التي حددتها المجلة.

### هل يستطيع المحرر سحب البحث نيابة عن المؤلف؟
لا، الإضافة مخصصة للمؤلف كخدمة ذاتية موثقة لحماية حقوق النشر وتوثيق موافقة الباحثين المشاركين. يمتلك المحرر أدوات OJS القياسية للرفض أو إلغاء النشر.

---

## Architecture & Directory Structure / هيكل الملفات والبرمجة

```text
withdrawResearchPlugin/
├── classes/
│   ├── handlers/
│   │   ├── AuthorWithdrawHandler.php     # Controller for author modal and withdrawal execution
│   │   └── WithdrawSettingsHandler.php   # Controller for journal manager settings & saving
│   ├── mail/
│   │   └── WithdrawalMailable.php        # OJS 3.5 Laravel Mailable for email notifications
│   └── WithdrawalService.php             # Core logic: permissions, checks, status update & audit log
├── css/
│   └── withdrawResearch.css              # Overlay layering, modal styles, and action buttons
├── js/
│   └── withdrawResearch.js               # Injects button beside Submission Library & launches modal
├── locale/
│   ├── ar/locale.po                      # Arabic translations
│   ├── ar_IQ/locale.po                   # Iraqi Arabic translations
│   ├── en/locale.po                      # English translations
│   └── en_US/locale.po                   # English (US) translations
├── templates/
│   ├── author/
│   │   └── withdrawModal.tpl             # Modal template with policy, dropdown, notes & confirmation
│   └── settings/
│       └── settings.tpl                  # Settings modal template (stages, policies, notifications)
├── index.php                             # Plugin bootstrap entry point
├── version.xml                           # OJS plugin descriptor
├── LICENSE                               # GNU General Public License v3
└── README.md                             # Comprehensive bilingual documentation
```

---

## License / الترخيص

This plugin is licensed under the **GNU General Public License v3.0 (GPLv3)**.  
هذه الإضافة مرخصة بموجب **رخصة جنو العمومية الإصدار 3 (GPLv3)**.
