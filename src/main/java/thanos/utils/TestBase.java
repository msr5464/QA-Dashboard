package thanos.utils;

import java.io.File;
import java.lang.reflect.Method;

import org.testng.annotations.BeforeSuite;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Listeners;
import org.testng.annotations.Optional;
import org.testng.annotations.Parameters;

/**
 * This class contains all the TestNG anotations related functions and Data providers
 * @author MukeshR
 */
@Listeners(thanos.utils.TestListener.class)
public class TestBase
{
	public static ThreadLocal<Config[]> threadLocalConfig = new ThreadLocal<Config[]>();
	
	@DataProvider(name = "getTestConfig")
	public Object[][] getTestConfiguration(Method method)
	{
		Config testConfig = new Config();
		testConfig.testcaseName = method.getName();
		testConfig.testcaseClass = method.getDeclaringClass().getName();
		threadLocalConfig.set(new Config[] { testConfig });
		System.out.println("Running test - '" + testConfig.testcaseName + "' from class - '" + testConfig.testcaseClass + "'...");
		return new Object[][] { { testConfig } };
	}
	
	@BeforeSuite(alwaysRun = true)
	@Parameters({ "resultsDirectory", "remoteExecution", "debugMode" })
	public void beforeSuiteExecution(@Optional String resultsDirectory, @Optional String remoteExecution, @Optional String debugMode)
	{
		Config.resultsDirectory = resultsDirectory;
		Config.isRemoteExecution = remoteExecution == null ? false : remoteExecution.equalsIgnoreCase("true");
		Config.isDebugMode = debugMode == null ? false : debugMode.equalsIgnoreCase("true");
	}
	
	public String getEntityConfigPath(String entityName)
	{
		return System.getProperty("user.dir") + File.separator + "Parameters" + File.separator + entityName.replaceAll(" ", "") + File.separator;
	}
}