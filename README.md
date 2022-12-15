<img src="https://raw.githubusercontent.com/msr5464/BasicFramework/master/Drivers/ThanosLogo.png" title="Powered by Thanos and created by Mukesh Rajput" height="50">

# QUALITY DASHBOARD
The purpose of creating this dashboard is to track the overall Quality Metrics of any product/service within any organization which has a lot of teams and so it's difficult to track each team manually.

### QA Dashboard can help to track:
1. Progress in terms of `Test Coverage` like how many total testcases we have, distribution in terms of priority like P0, P1, P2 etc, Automation coverage and many more

2. Progress report of `Automation Stability` and Execution time for each stream/project and other relevant trend charts around it

3. `Bugs Metrics` ie. data around total tickets tested, numbers of Bugs found in production or staging environment, what is their priority and other relevant trend charts around it

4. `Code Coverage` data is helpful to track the code coverage for developer's code via Unit Tests (written by devs). It supports not only Line Coverage but also Statement Coverage, Branch Coverage & Function Coverage (default is line coverage)


### Documentation:
To know each and everything about this QA-Dashboard, refer to this [presentation document](Documentation/QA-Dashboard-ppt.pdf)

## Screenshots:

### Home Page of QA Dashboard:
![Home Page](Documentation/homePage.png "Home Page")

### Test Coverage Data for all the Projects:
![Testrail Page](Documentation/testrailPage1.png "Testrail Numbers Page for all the Projects")

### Test Coverage Data for a selected Project:
![Testrail Page](Documentation/testrailPage2.png "Testrail Numbers Page for one Project")

### Automation Stability Data for all the Projects:
![Results Page](Documentation/resultsPage1.png "Automation Result Numbers for all the Projects")

### Automation Stability Data for a selected Project:
![Results Page](Documentation/resultsPage2.png "Automation Result Numbers for one Project")

### Bug Metrics Data for all the Projects:
![Bugs Page](Documentation/bugsPage1.png "Bug Metrics Page for all the Projects")

### Bug Metrics Data for a selected Project:
![Bugs Page](Documentation/bugsPage2.png "Bug Metrics Page for one Project")

### Code Coverage Data for all the Projects:
![Bugs Page](Documentation/unitTestsPage1.png "Unit Tests Coverage Page for all the Projects")

### Code Coverage Data for selected Project:
![Bugs Page](Documentation/unitTestsPage2.png "Unit Tests Coverage Page for one Project")


### Lets talk about some of the hidden features:
##### 1. Dark & Light Mode View:
To enable or disable the darkmode use query param as [`darkmode=1`](http://localhost:8282/testrail.php?darkmode=1) (by default its `darkmode=1`)
##### 2. Pod View for bigger teams:
In case your Group or Vertical is quite big then you can convert the dashboard to only show high-level data for bigger teams ie. pods (1 pod = 2 or more smaller teams). Use query param as [`podview=1`](http://localhost:8282/testrail.php?podview=1) (by default its `podview=0`)

**Note:** All query params get stored in **cookies** so that you don't need to pass them in URL every time, this means make sure to use `darkmode=1` or`podview=0` to turn off these views accordingly.


## Tools/Languages Used:
#### For Web Portal:
##### HTML
##### PHP
##### JavaScript
##### MYSQL

#### For Data Populator:
##### JAVA
##### TESTNG
##### GRADLE


## Prerequisites:

#### 1. Install php server in your machine
If its Mac, php server comes installed by default, just run this cmd to start server: `php -S localhost:8282`

#### 2. Install mysql server
And then start the server using: `mysql.server start`

#### 3. Setup Database and tables
Use [this basic db dump file](Documentation//db-dump.sql "db-dump.sql") to create database and insert few dummy entries, on the successful execution of this script you should get minimum 5 tables (`<entityName>_results`, `<entityName>_jira`, `<entityName>_testrail`, `<entityName>_bugs`, `<entityName>_units`) along with configurations table created inside a database named as `thanos`

#### 4. Run these queries to change createdAt of dummy data:
```
set global sql_mode='';
Update payment_gateway_testrail set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from payment_gateway_testrail order by id desc limit 1) as X) DAY) where id > 0;
Update payment_gateway_results set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from payment_gateway_results order by id desc limit 1) as X) DAY) where id > 0;
Update payment_gateway_jira set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from payment_gateway_jira order by id desc limit 1) as X) DAY) where id > 0;
Update payment_gateway_bugs set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from payment_gateway_bugs order by id desc limit 1) as X) DAY) where id > 0;
Update payment_gateway_units set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from payment_gateway_units order by id desc limit 1) as X) DAY) where id > 0;
Update all_entities_testrail set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from all_entities_testrail order by id desc limit 1) as X) DAY) where id > 0;
Update all_entities_results set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from all_entities_results order by id desc limit 1) as X) DAY) where id > 0;
Update all_entities_jira set createdAt=DATE_ADD(createdAt, INTERVAL (Select DATEDIFF(now(),createdAt) from (Select createdAt from all_entities_jira order by id desc limit 1) as X) DAY) where id > 0;

```

#### 4. Clone this repo in your machine
After cloning the repo, navigate to the `Website/utils` folder and update database credentials in [this file](Website/utils/constants.php "constants.php")

## Start Web Server:
Start the php server and navigate to `http://localhost:8282`, you should see the home page as shown in the screenshots above.


## How I am populating data:
For this, please refer to `src` folder in the root directory, it contains whole framework for poulating data into these tables (using Testrail & Jira apis).

1. For `<entityName>_results table` - I have updated my automation frameworks to insert required data in `results` table at the end of each automation execution.

2. For `<entityName>_testrail table` - Please don't be confused with the table name, it is not only limited to testrail numbers, but these numbers can also be fetched from any testcase management tool. I have used APIs of Testrail to fetch all the required numbers from Testrail and then inserting them in the `testrail` table twice a week.

3. For `<entityName>_jira` & `<entityName>_bugs table`- Again don't be confused with the table name, it is not only limited to jira numbers, but these numbers can also be fetched from any ticket management tool. I have used APIs of Jira to fetch all the required numbers from Jira and then inserting them in the `jira` table twice a week.

4. For `<entityName>_units table` - This table is used to store the code coverage data sent by developer's pipeline into GCP bucket and from ther our worker read csv files and populate data into this table every day.

Point is, no matter if you insert data manually or via automation scripts, till the time you are able to add data in these 5 tables daily/weekly, your dashboard will keep showing updated data and graphs.


## Steps for onboarding your Organization to QA Dashboard:
1. Create a new Test class file with syntax: `<entityName>Numbers.java` at path  `src/test/java/thanos/`
2. Update `entityName` variable as per your Entity Name in test class file.
4. Create directory inside `parameters/` path with `entityName` variable's name.
    
    #### For Test Coverage:
    1. Take reference from existing test class files like: [PaymentGatewayNumbers.java](src/test/java/thanos/PaymentGatewayNumbers.java "PaymentGatewayNumbers.java") and add a new test method `fetchTestCoverageData` in your test class.
    2. Inside newly created directory `parameters/<Entity Name>`, add new json file with name `TestRailConfig.json`
    3. In `TestRailConfig.json`, list out all the TestRail suite names of your Entity along with suite id and project id. Refer: [TestRailConfig.json](parameters/PaymentGateway/TestRailConfig.json "TestRailConfig.json") for more details.
    
    #### For Automation Results:
    1. Create the jar file of this repo & put in your automation framework repo as a dependency.
    2. Thereafter call this function `ResultsHelper.createAutomationResultsCsvAndUploadToGcpBucket` and pass all the necessary parameters, this will help to put your automation results data in GCP bucket in the form of csv file.
    3. Then in this (QA Dashbaord) repo, create new json file with name `AutomationConfig.json`, list out all the automation project names of your Entity along with platform type & pod name . Refer: [AutomationConfig.json](parameters/PaymentGateway/AutomationConfig.json "AutomationConfig.json") for more details.
    4. Finally, add 1 more testcase (in above created your Entity test class) to fetch the automation reports from GCP bucket and insert to Thanos DB, you can take reference from `fetchAutomationStabilityData` testcase present in [PaymentGatewayNumbers.java](src/test/java/thanos/PaymentGatewayNumbers.java "PaymentGatewayNumbers.java").

    #### For Unit Tests Coverage
    1. Unit test coverage data is generated using the developer's pipeline, if this data in not available then ask developers to have code coverage numbers for each repo.
    2. Developer's Repo will upload data to GCP bucket (as csv file) by doing 1 time integration with GCP bucket(using ci/cd pipelines). File format and sample code [can be found here](https://docs.google.com/spreadsheets/d/1SjmPT591qUQzld6syw8jynKNDixrwdXdjcyD7TvjdBw/edit#gid=120821750)
    3. Once this integration is done & csv files start uploading to bucket on every dev code build, add 1 more testcase (in above created your Entity test class) to fetch the unit test coverage reports from GCP bucket and insert to Thanos DB, you can take reference from `fetchCodeCoverageData` testcase present in [PaymentGatewayNumbers.java](src/test/java/thanos/PaymentGatewayNumbers.java "PaymentGatewayNumbers.java").
    
    #### For Bug Metrics
    1. Take reference from existing test class files for eg: [PaymentGatewayNumbers.java](src/test/java/thanos/PaymentGatewayNumbers.java "PaymentGatewayNumbers.java") and add a new test method `fetchBugMetricsData` in your test class.

    2. Define the filters in code, as shown in the `fetchBugMetricsData` method, like from which data you want to fetch numbers, what all Jira ticket types you want to capture along with statuses.

    3. Then create, custom fields in Jira (you will require admin access of Jira for this) so that people can start filling variour data points again every bug found like - environment (staging/production), bugFoundBy (manual/automation/crashes/actual users etc) and bugCategory (android/ios/backend/web/mweb etc) and attach these new fields in Jira bug screen.
    4. Now, put custom field IDs of these new fields in the java code
    5. Inside newly created directory `parameters/<Entity Name>`, add new json file with name `JiraConfig.json`
    6. In `JiraConfig.json`, list out all the Jira project names of your Entity along with project key. Refer: [JiraConfig.json](parameters/PaymentGateway/JiraConfig.json "JiraConfig.json") for more details.
<br>

5. After this, add new entry for your entity into `configurations` table present in thanos DB.
6. Once all the steps are done, data will start populating into respective DB tables and you can schedule to run this code everyday or multiple times a day as per requirements. 


## Debugging:
1. In case your mysql server is not able to execute some of the queries and showing error something like: 
`Error Code: 1055. Expression #2 of SELECT list is not in GROUP BY clause and contains nonaggregated column 'thanos.a.totalTicketsTested' which is not functionally dependent on columns in GROUP BY clause; this is incompatible with sql_mode=only_full_group_by`.<br>
Then run this query in the mysql terminal or UI: `set global sql_mode='';` and start the sql connection again, please remember that if you restart the mysql server or your laptop then you might need to execute this cmd again.

2. If you are seeing `Error!: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)` on the dashboard it means your db credentials are incorrect, you need to modify them in [this file](Website/utils/constants.php "constants.php")

In case there are some more bugs/issues, you can report them to the `Issues` section of this repo.


## References:
This dashboard has been created by using the public services of [Fusion Charts](https://www.fusioncharts.com/).
A big Thanks to the Fusion Charts team for putting such wonderful documentation which helped me in swift integration.


## Creator:
Mukesh Rajput, For any further questions, contact [@mukesh.rajput](https://www.linkedin.com/in/mukesh-rajput)