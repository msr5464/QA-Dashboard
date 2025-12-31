# Onboarding & Data Population Guide

## 📦 Initial Database Setup

Before onboarding new organizations, ensure the database is set up with the provided dump.

### 1. Import Database
Use [`Documentation/db-dump.sql`](db-dump.sql) to create the database and insert dummy entries.
- **Default Database Name**: `qa-dashboard`
- **Default Tables**: Creates tables for `PaymentGateway` entity and generic `entities_*` tables.

### 2. Update Dummy Data Timestamps
To make the dummy data appear recent (so charts show data for "today"), run the following SQL queries in your database tool (e.g., Workbench, Sequel Pro):

```sql
set global sql_mode='';

-- Update generic tables
UPDATE entities_results SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM entities_results ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE entities_tests_all SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM entities_tests_all ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE entities_tests_fct SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM entities_tests_fct ORDER BY id DESC LIMIT 1) as t) DAY);

-- Update PaymentGateway tables
UPDATE paymentgateway_jira_bugs SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM paymentgateway_jira_bugs ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE paymentgateway_jira_tickets SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM paymentgateway_jira_tickets ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE paymentgateway_results SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM paymentgateway_results ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE paymentgateway_tests_all SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM paymentgateway_tests_all ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE paymentgateway_tests_data SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM paymentgateway_tests_data ORDER BY id DESC LIMIT 1) as t) DAY);
UPDATE paymentgateway_tests_fct SET createdAt = DATE_ADD(createdAt, INTERVAL (SELECT DATEDIFF(NOW(), createdAt) FROM (SELECT createdAt FROM paymentgateway_tests_fct ORDER BY id DESC LIMIT 1) as t) DAY);
```

## 🚀 Onboarding Your Organization

Follow these steps to add a new entity (Organization/Team) to the dashboard.

### Step 1: Create Test Class
1. Go to `src/test/java/thanos/`.
2. Create a new Java class: `<TeamName>.java` (e.g., `Onboarding.java`, `Payments.java`, `Cards.java`).
3. Set the `entityName` variable to your organization name (e.g., `"PaymentGateway"`).
4. Set the `teamName` variable to match your directory name from Step 2 (e.g., `"Onboarding"`, `"Payments"`).

### Step 2: Create Configuration Directory
1. Create a directory: `Parameters/<TeamName>/` (e.g., `Parameters/Onboarding/`, `Parameters/Payments/`).

### Step 3: Configure Data Sources
**For Test Coverage:**
1. Add `TestRailConfig.json` to your new directory.
2. List all TestRail suite names, suite IDs, and project IDs.

**For Bug Metrics:**
1. Add `JiraConfig.json` to your new directory.
2. List Jira project names and keys.

### Step 4: Update PHP Configuration
1. Open [`Website/utils/config.php`](../Website/utils/config.php).
2. Add your entity to the `$ENTITY_CONFIGURATIONS` array:

```php
[
    'entityName' => 'Your Entity Name',
    'tableNamePrefix' => 'your_entity_prefix', // Must match Java entityName
    'isActive' => true,
    'showFctTests' => true,
    // ... enable other flags as needed
]
```

---

## 💾 Data Population

The dashboard relies on a Java framework to fetch data from TestRail and Jira databases.

### Execution
Run the test methods in your `<TeamName>.java` class:
- `fetchFctTestcasesFromTestrail()`: Populates FCT test coverage data.
- `fetchAllTestcasesFromTestrail()`: Populates all test coverage data.
- `fetchBugMetricsData()`: Populates `<prefix>_jira_bugs` and `<prefix>_jira_tickets` tables.
- `fetchAutomationStabilityData()`: Populates `<prefix>_results` table.

### Scheduling
Schedule these Java tests to run periodically (e.g., via Jenkins/Crontab) to keep the dashboard up-to-date.

### Configuration
Update `Parameters/config.properties`:
- `testRailHostUrl`
- `JiraHostUrl`
- Database credentials for the Java app
