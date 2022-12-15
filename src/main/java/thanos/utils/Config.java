package thanos.utils;

import java.io.File;
import java.io.FileInputStream;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Properties;
import java.util.stream.Collectors;
import org.apache.commons.lang3.StringUtils;
import org.apache.commons.lang3.exception.ExceptionUtils;
import org.testng.ITestResult;
import org.testng.Reporter;
import org.testng.asserts.SoftAssert;

/**
 * This class is the main executor class which pass all the parameters and cinfugurations to each testcase
 * @author MukeshR
 */
public class Config
{
	public static String resultsDirectory;
	public static boolean isRemoteExecution = true;
	public static boolean isDebugMode = false;
	public static String osName = System.getProperty("os.name");
	public static HashMap<String, TestDataReader> testDataReaderHashMap = new HashMap<String, TestDataReader>();
	public boolean endExecutionOnfailure = false;
	String testLog = "";
	String testEndTime;
	public String testcaseName;
	public String testcaseClass;
	String testStartTime;
	SoftAssert softAssert = null;
	Properties runTimeProperties = null;
	int testcasesRemaining = 0;
	boolean testResult = true;
	
	public Config()
	{
		softAssert = new SoftAssert();
		runTimeProperties = new Properties();
		Properties properties = null;
		// Code to read .properties file and put key value pairs into RunTime Property file
		try
		{
			String parametersPath = System.getProperty("user.dir") + File.separator + "parameters" + File.separator;
			FileInputStream fileInputStream = new FileInputStream(parametersPath + "config.properties");
			properties = new Properties();
			properties.load(fileInputStream);
			fileInputStream.close();
		}
		catch (Exception e)
		{
			logComment("Exception while reading config.properties file...");
			e.printStackTrace();
		}
		
		Enumeration<Object> enumeration = properties.keys();
		while (enumeration.hasMoreElements())
		{
			String str = (String) enumeration.nextElement();
			putRunTimeProperty(str, (String) properties.get(str));
		}
		
		// override param values if passed through TestNG.xml
		if (!StringUtils.isEmpty(resultsDirectory))
		{
			putRunTimeProperty("ResultsDirectory", resultsDirectory);
		}
		else
		{
			resultsDirectory = System.getProperty("user.dir") + File.separator + "test-output";
			putRunTimeProperty("ResultsDirectory", resultsDirectory);
		}
		
		// Putting values into variables from RunTime properties
		endExecutionOnfailure = endExecutionOnfailure || getRunTimeProperty("EndExecutionOnFailure").equalsIgnoreCase("true");
		isRemoteExecution = isRemoteExecution || getRunTimeProperty("RemoteExecution").equalsIgnoreCase("true");
		isDebugMode = isDebugMode || getRunTimeProperty("debugMode").equalsIgnoreCase("true");
	}
	
	/**
	 * Add the given key value pair in the Run Time Properties
	 * @param key
	 * @param value
	 */
	public void putRunTimeProperty(String key, String value)
	{
		if (isDebugMode)
			logComment("Putting RunTime key-" + key.toLowerCase() + " value:-'" + value + "'");
		runTimeProperties.put(key.toLowerCase(), value);
	}
	
	/**
	 * Add the given key value pair in the Run Time Properties
	 * @param key
	 * @param value
	 */
	public void putRunTimeProperty(String key, Object value)
	{
		String keyName = key.toLowerCase();
		runTimeProperties.put(keyName, value);
		if (isDebugMode)
			logComment("Putting Run-Time key-" + keyName + " value:-'" + value + "'");
	}
	
	/**
	 * Add the given key value pair in the Run Time Properties
	 * @param map - HashMap
	 */
	public void putRunTimeProperty(HashMap<String, Integer> hashMap)
	{
		Map map = hashMap.entrySet().stream().collect(Collectors.toMap(entry -> entry.getKey().toLowerCase(), entry -> entry.getValue()));
		runTimeProperties.putAll(map);
	}
	
	public void putHashmapStringTypeAsRunTimeProperty(HashMap<String, String> hashMap)
	{
		Map map = hashMap.entrySet().stream().collect(Collectors.toMap(entry -> entry.getKey().toLowerCase(), entry -> entry.getValue()));
		runTimeProperties.putAll(map);
	}
	
	/**
	 * Get the Run Time Property value
	 * @param key name whose value is needed
	 * @return value of the specified key
	 */
	public String getRunTimeProperty(String key)
	{
		String keyName = key.toLowerCase();
		String value = "";
		try
		{
			value = runTimeProperties.get(keyName).toString();
			if (isDebugMode)
				logComment("Read RunTime Property -'" + keyName + "' as -'" + value + "'");
		}
		catch (Exception e)
		{
			if (isDebugMode)
				logComment("'" + key + "' not found in Run Time Properties");
			return null;
		}
		return value;
	}
	
	/**
	 * Replaces the arguments like {$someArg} present in input string with its value from RuntimeProperties
	 * @param input string in which some Argument is present
	 * @return replaced string
	 */
	public String replaceArgumentsWithRunTimeProperties(String input)
	{
		if (input.contains("{$"))
		{
			int index = input.indexOf("{$");
			input.length();
			input.indexOf("}", index + 2);
			String key = input.substring(index + 2, input.indexOf("}", index + 2));
			String value = getRunTimeProperty(key);
			input = input.replace("{$" + key + "}", value);
			return replaceArgumentsWithRunTimeProperties(input);
		}
		return input;
	}
	
	public void logComment(String message)
	{
		Log.Comment(this, message);
	}
	
	public void logCommentJson(String message, String color)
	{
		Log.CommentJson(this, message, color);
	}
	
	public void logColorfulComment(String message, String color)
	{
		Log.Comment(this, message, color);
	}
	
	public void logCommentForDebugging(String message)
	{
		if (isDebugMode)
			Log.Comment(this, message);
	}
	
	public void logWarning(String message)
	{
		Log.Warning(this, message);
	}
	
	public void logWarning(String message, boolean pageCapture)
	{
		Log.Warning(this, message, pageCapture);
	}
	
	public void logWarning(String what, String expected, String actual)
	{
		String message = "Expected '" + what + "' was :-'" + expected + "'. But actual is '" + actual + "'";
		Log.Warning(this, message);
	}
	
	public void logFail(String message)
	{
		testResult = false;
		Log.Fail(this, message);
	}
	
	public void logFailToEndExecution(String message)
	{
		endExecutionOnfailure = true;
		testResult = false;
		Log.Fail(this, message);
	}
	
	public <T> void logFail(String what, T expected, T actual)
	{
		testResult = false;
		String message = "Expected '" + what + "' was :-'" + expected + "'. But actual is '" + actual + "'";
		Log.Fail(this, message);
	}
	
	public void logPass(String message)
	{
		Log.Pass(this, message);
	}
	
	public <T> void logPass(String what, T actual)
	{
		String message = "Verified '" + what + "' as :-'" + actual + "'";
		Log.Pass(this, message);
	}
	
	public void logExceptionAndFail(Throwable e)
	{
		logExceptionAndFail("", e);
	}
	
	public void logExceptionAndFail(String message, Throwable e)
	{
		testResult = false;
		String fullStackTrace = ExceptionUtils.getStackTrace(e);
		Log.Fail(this, message + "\nException Message:- " + fullStackTrace);
	}
	
	public void logException(String message, Throwable e)
	{
		if (e.getMessage() == null)
		{
			logWarning(message);
			if (isDebugMode)
			{
				String fullStackTrace = ExceptionUtils.getStackTrace(e);
				Log.Warning(this, " \nFull Exception Stacktrace:- \n" + fullStackTrace);
			}
		}
		else
		{
			logWarning(message + ". \nException Message:- " + e.getMessage());
		}
		if (isDebugMode)
		{
			String fullStackTrace = ExceptionUtils.getStackTrace(e);
			Log.Warning(this, " \nFull Exception Stacktrace:- \n" + fullStackTrace);
		}
	}
	
	/**
	 * Get the cached TestDataReader Object for the given sheet. If it is not
	 * cached, it will be cached for future use
	 * @param sheetName
	 * @return TestDataReader object or null if object is not in cache
	 */
	public TestDataReader getExcelSheet(String sheetName)
	{
		String excelFilePath = System.getProperty("user.dir") + File.separator + "parameters" + File.separator + "TestDataSheet.xls";
		TestDataReader testDataReader = testDataReaderHashMap.get(excelFilePath + sheetName);
		if (testDataReader == null)
		{
			// cache for future use
			synchronized (Config.class)
			{
				testDataReader = new TestDataReader(this, sheetName, excelFilePath);
				testDataReaderHashMap.put(excelFilePath + sheetName, testDataReader);
			}
		}
		return testDataReader;
	}
	
	/**
	 * End Test
	 * @param result - ITestResult
	 */
	public void endTest(ITestResult result)
	{
		testEndTime = CommonUtilities.getCurrentDateTime("dd-MM-yyyy HH:mm:ss");
		endExecutionOnfailure = false;
		List<String> reporterOutput = Reporter.getOutput(result);
		
		if (testResult)
		{
			if (!CommonUtilities.listContainsString(reporterOutput, "<B>Failure occured in test '" + testcaseName + "' Ended on '"))
				logPass("<B>Test Passed '" + testcaseName + "' of Class '" + testcaseClass + "' Ended on '" + testEndTime + "'</B>");
		}
		else
		{
			if (!CommonUtilities.listContainsString(reporterOutput, "<B>Test Passed '" + testcaseName + "' Ended on '"))
				logFail("<B>Failure occured in test '" + testcaseName + "' of Class '" + testcaseClass + "' Ended on '" + testEndTime + "'</B>");
		}
		
		if (testStartTime != null)
		{
			long minutes = 0;
			long seconds = 0;
			DateFormat dateFormat = new SimpleDateFormat("dd-MM-yyyy HH:mm:ss");
			String minuteOrMinutes = " ";
			String secondOrSeconds = "";
			try
			{
				long timeinMillis = (dateFormat.parse(testEndTime).getTime() - dateFormat.parse(testStartTime).getTime()) / 1000;
				minutes = timeinMillis / 60;
				seconds = timeinMillis % 60;
				if (minutes > 1)
					minuteOrMinutes = "s ";
				if (seconds > 1)
					secondOrSeconds = "s";
			}
			catch (Exception e)
			{
			}
			if (!CommonUtilities.listContainsString(reporterOutput, "<font color='Blue'><B>Total time taken by Test '" + testcaseName + "' : '"))
				logComment("<font color='Blue'><B>Total time taken by Test '" + testcaseName + "' of Class '" + testcaseClass + "' : '" + minutes + " minute" + minuteOrMinutes + seconds + " second" + secondOrSeconds + "' </B></font>");
		}
	}
}