package thanos.utils;

import java.lang.reflect.Method;
import org.testng.annotations.BeforeMethod;
import org.testng.annotations.Listeners;

/**
 * This class contains all the TestNG anotations related functions and Data providers
 * @author MukeshR
 *
 */
@Listeners(thanos.utils.TestListener.class)
public class TestBase {
	public static ThreadLocal<Config[]> threadLocalConfig = new ThreadLocal<Config[]>();
	protected Config testConfig;

	@BeforeMethod
	public void getTestConfiguration(Method method) {
		testConfig = new Config();
		testConfig.testcaseName = method.getName();
		threadLocalConfig.set(new Config[] { testConfig });
	}
}