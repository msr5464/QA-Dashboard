package thanos.utils;

import java.lang.reflect.Method;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Listeners;

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
		threadLocalConfig.set(new Config[] { testConfig });
		return new Object[][] { { testConfig } };
	}
}