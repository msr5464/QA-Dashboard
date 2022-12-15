package thanos;

import java.io.File;
import org.testng.annotations.Test;
import thanos.helpers.ResultsHelper;
import thanos.utils.Config;
import thanos.utils.TestBase;

public class TestDemo extends TestBase
{
	
	@Test(dataProvider = "getTestConfig", description = "Example showing, how to send Automation Results data into GCP Bucket, once you put this repo as jar file in your project")
	public void uploadAutomationResultsToBucket(Config testConfig)
	{
		String entityName = "PaymentGateway";
		String createdAt = "05/05/21 6:10";
		String projectName = "PaymentPage";
		String environment = "Staging";
		String groupName = "regression";
		String duration = "135";
		String percentage = "16.30";
		String totalCases = "92";
		String passedCases = "15";
		String failedCases = "77";
		String buildTag = "1234509";
		String resultLink = "https://blabla/primeReports/1234509/overview-features.html";
		
		ResultsHelper resultsHelper = new ResultsHelper();
		String gcpBucketAuthKeyLocation = System.getProperty("user.dir") + File.separator + "parameters" + File.separator + "gcp-bucket-config.json";
		resultsHelper.createAutomationResultsCsvAndUploadToGcpBucket(testConfig, gcpBucketAuthKeyLocation, entityName, createdAt, projectName, environment, groupName, duration, percentage, totalCases, passedCases, failedCases, buildTag, resultLink);
	}
}
