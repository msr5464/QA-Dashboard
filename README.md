<img src="https://raw.githubusercontent.com/msr5464/BasicFramework/master/Drivers/ThanosLogo.png" title="Powered by Thanos and created by Mukesh Rajput" height="50">

# QUALITY DASHBOARD
The purpose of creating this dashboard is to track the overall Quality Metrics of any product/service within any organization which has a lot of teams and so its difficult to track each team manually.

### QA Dashboard can help to track data for:
1. Daily progress report of `Automation Results` and Execution time for each stream/product

2. Progress in terms of `Testcases in Testrail` (or in any other testcase management tool), like how many total testcases we have, distribution in terms of manual/automation etc, distribution in terms of priority testcases, automation coverage

3. Progress in terms of total tickets tested along with numbers of `Bugs reported in Jira` (or any other ticket management tool), how many bugs are from the production/staging, what is the priority etc.

4. `Unit Tests Coverage` is helpful to track the code coverage for developer's code via Unit Tests (written by devs). It supports not only Line Coverage but also Statement Coverage, Branch Coverage & Function Coverage (default is line coverage).


### Live Demo of QA Dashboard:
Want to see running demo for Quality Dashboard, visit this site: https://qa-dashboard.000webhostapp.com/


## Prerequisites:

#### 1. Install php server in your machine
If its Mac, php server comes installed by default, just run this cmd to start server: `php -S localhost:8282`

#### 2. Install mysql server
And then start the server using: `mysql.server start`

#### 3. Setup Database and tables
Use [this basic mysql dump file](utils/mysql-dump.sql "mysql-dump.sql") to create database and insert few dummy entries, on the successful execution of this script you should get minimum 4 tables (`<teamName>_results`, `<teamName>_jira`, `<teamName>_testrail`, `<teamName>_units`) created inside a database named as `thanos`

#### 4. Clone this repo in your machine
After cloning the repo, navigate to the `utils` folder and update database credentails in [this file](utils/constants.php "constants.php")

## Start Web Server:
Start the php server and navigate to `http://localhost:8282`, you should see the home page as shown in screenshots below.


## Screenshots:

### Home Page of QA Dashboard:
![Home Page](screenshots/homePage.png "Home Page")

### Automation Result Numbers for all the Projects:
![Results Page](screenshots/resultsPage1.png "Automation Result Numbers for all the Projects")

### Automation Result Numbers for one Project:
![Results Page](screenshots/resultsPage2.png "Automation Result Numbers for one Project")

### Testrail Numbers Page for all the Projects:
![Testrail Page](screenshots/testrailPage1.png "Testrail Numbers Page for all the Projects")

### Testrail Numbers Page for one Project:
![Testrail Page](screenshots/testrailPage2.png "Testrail Numbers Page for one Project")

### Bug Metrics Page for all the Projects:
![Bugs Page](screenshots/bugsPage1.png "Bug Metrics Page for all the Projects")

### Bug Metrics Page for one Project:
![Bugs Page](screenshots/bugsPage2.png "Bug Metrics Page for one Project")

### Unit Tests Coverage Page for all the Projects:
![Bugs Page](screenshots/unitTestsPage1.png "Unit Tests Coverage Page for all the Projects")

### Unit Tests Coverage Page for one Project:
![Bugs Page](screenshots/unitTestsPage2.png "Unit Tests Coverage Page for one Project")


## Tools/Languages Used:
##### HTML
##### PHP
##### JavaScript
##### MYSQL


## How I am populating data in mysql tables:
1. For `<teamName>_results table` - I have updated my automation framework to insert required data in `results` table at the end of each automation execution.

2. For `<teamName>_testrail table` - Please don't be confused with the table name, it is not only limited to testrail numbers, but these numbers can also be fetched from any testcase management tool. I have used APIs of Testrail to fetch all the required numbers from Testrail and then inserting them in the `testrail` table twice a week.

3. For `<teamName>_jira table` - Again don't be confused with the table name, it is not only limited to jira numbers, but these numbers can also be fetched from any ticket management tool. I have used APIs of Jira to fetch all the required numbers from Jira and then inserting them in the `jira` table twice a week.

4. For `<teamName>_units table` - This table is used to store the code coverage data sent by developer's pipeline into GCP bucket and from ther our worker read csv files and populate data into this table everyday.

Point is, no matter if you insert data manually or via automation scripts, till the time you are able to add data in these 4 tables daily/weekly, your dashboard will keep showing updated data and graphs.


## Debugging:
1. Incase your mysql server is not able to execute some of the queries and showing error something like: 
`Error Code: 1055. Expression #2 of SELECT list is not in GROUP BY clause and contains nonaggregated column 'thanos.a.totalTicketsTested' which is not functionally dependent on columns in GROUP BY clause; this is incompatible with sql_mode=only_full_group_by`.<br>
 Then run this query in the mysql terminal or UI: `set global sql_mode='';` and start the sql connection again, please remember that if you restart the mysql server or your laptop then you might need to execute this cmd again.

2. If you are seeing `Error!: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)` on the dashboard it means your db credentials are incorrect, you need to modify them in [this file](utils/constants.php "constants.php")

Incase there are some more bugs/issues, you can report to `Issues` section of this repo.


## References:
This dashboard has been creating by using public services of [Fusion Charts](https://www.fusioncharts.com/).
A big Thanks to Fusion Charts team for putting such a wonderful documentation which helped me in swift integration.


## Creator:
Mukesh Rajput, For any further questions, contact [@mukesh.rajput](https://www.linkedin.com/in/mukesh-rajput)
