package thanos;

import org.testng.annotations.Test;
import thanos.helpers.ResultsHelper;
import thanos.utils.Config;
import thanos.utils.TestBase;

public class TestDemo extends TestBase
{
	
	@Test(dataProvider = "getTestConfig", description = "Fetch the automation results and update it to DB", groups = { "smoke" })
	public void GenericProjectReportTest(Config testConfig)
	{
		testConfig.logComment("This is a test report being uploaded to GCP bucket");
		String entityName = "PaymentGateway";
		String createdAt = "05/05/21 6:10";
		String projectName = "Iron";
		String environment = "Staging";
		String groupName = "fullDataPopulator";
		String duration = "3:15:43";
		String percentage = "16.3";
		String totalCases = "92";
		String passedCases = "15";
		String failedCases = "77";
		String buildTag = "1234509";
		String resultLink = "https://qa-dashboard.abc.io/primeReports/6075d82f7952a904a5f50790/overview-features.html";
		
		ResultsHelper resultsHelper = new ResultsHelper();
		resultsHelper.createAutomationResultsCsvAndUploadToGcpBucket(testConfig, entityName, createdAt, projectName, environment, groupName, duration, percentage, totalCases, passedCases, failedCases, buildTag, resultLink);
	}
}
