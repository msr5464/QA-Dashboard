package thanos;

import java.io.File;

import org.testng.annotations.Test;

import thanos.helpers.ResultsHelper;
import thanos.utils.CommonUtilities;
import thanos.utils.Config;
import thanos.utils.TestBase;
import thanos.utils.TriggerTestNgXmlFile;

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
	
	@Test(dataProvider = "getTestConfig", description = "This Demo")
	public void testDemo(Config testConfig)
	{
		String projectName = "DataPopulator";
		String sendEmailTo = "your-email@example.com";
		String jobBuildTag = CommonUtilities.generateRandomAlphaNumericString(15);
		String groupNames = "dataPopulator";
		String sendReportOnSlack = "false";
		String branchName = "main";
		String debugMode = "false";
		TriggerTestNgXmlFile.remoteExecution = false;
		
		String args[] = { projectName, sendEmailTo, jobBuildTag, groupNames, sendReportOnSlack, branchName, debugMode };
		TriggerTestNgXmlFile.main(args);
	}
}
