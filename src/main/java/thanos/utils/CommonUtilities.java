package thanos.utils;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.Iterator;
import java.util.List;
import java.util.Random;
import org.json.JSONObject;
import com.google.gson.GsonBuilder;
import com.google.gson.JsonParser;

/**
 * This class will contain all the random functions
 * @author MukeshR
 */
public class CommonUtilities
{
	/**
	 * format the string as json
	 * @param input
	 * @return formatted json string
	 */
	public static String formatStringAsJson(String input)
	{
		return new GsonBuilder().setPrettyPrinting().create().toJson(new JsonParser().parse(input));
	}
	
	/**
	 * This Method is used to create folder at given path
	 * @param path
	 * @return
	 */
	public static boolean createFolder(String path)
	{
		File newdir = new File(path);
		boolean result = false;
		if (!newdir.exists())
		{
			try
			{
				Files.createDirectories(Paths.get(path));
				System.out.println("Directory created successfully : " + path);
				result = true;
			}
			catch (Exception se)
			{
				System.out.println("Exception while creating Directory : " + path);
				se.printStackTrace();
			}
		}
		else
		{
			System.out.println("Directory: " + path + " already Exist");
			result = true;
		}
		return result;
	}
	
	/**
	 * Check List Contains Given String
	 * @param list
	 * @param stringToMatch
	 * @return true/false
	 */
	public static boolean listContainsString(List<String> list, String stringToMatch)
	{
		Iterator<String> iter = list.iterator();
		while (iter.hasNext())
		{
			String tempString = iter.next();
			if (tempString.contains(stringToMatch))
			{
				return true;
			}
		}
		return false;
	}
	
	public static String createFileInResultsDirectory(Config testConfig, String subDirectoryName)
	{
		String fileName = testConfig.getRunTimeProperty("resultsDirectory") + File.separator + subDirectoryName + File.separator + testConfig.testcaseName + "_" + generateRandomAlphaNumericString(15) + "_" + new SimpleDateFormat("HH-mm-ss").format(new Date());
		createFolder(fileName.substring(0, fileName.lastIndexOf(File.separator)));
		return fileName;
	}
	
	public static File getResultsDirectory(Config testConfig)
	{
		File dest = new File(System.getProperty("user.dir") + File.separator + "test-output" + File.separator + "html" + File.separator);
		return dest;
	}
	
	/**
	 * This function return the URL of a file on runtime depending on LOCAL or OFFICIAL Run
	 * @param testConfig
	 * @param fileUrl
	 * @return
	 */
	public static String convertFilePathToHtmlUrl(String fileUrl)
	{
		String htmlUrl = "";
		htmlUrl = fileUrl.replace(File.separator, "/");
		return htmlUrl;
	}
	
	public static JSONObject getJsonObjectFromJsonFile(String jsonFilePath)
	{
		try
		{
			String content = new String(Files.readAllBytes(Paths.get(jsonFilePath)));
			return new JSONObject(content);
		}
		catch (IOException e)
		{
			e.printStackTrace();
		}
		return null;
	}
	
	/**
	 * This method is created to add static wait for given seconds of time
	 * @param testConfig
	 * @param seconds
	 */
	public static void waitForSeconds(Config testConfig, int seconds)
	{
		int milliseconds = seconds * 1000;
		try
		{
			testConfig.logComment("Waiting for '" + seconds + "' seconds");
			Thread.sleep(milliseconds);
		}
		catch (InterruptedException e)
		{
			testConfig.logExceptionAndFail("", e);
		}
	}
	
	/**
	 * Compare two integer, double or float type values using a generic function.
	 * @param testConfig
	 * @param what
	 * @param expected
	 * @param actual
	 */
	public static <T> void compareEquals(Config testConfig, String what, T expected, T actual)
	{
		if (expected == null & actual == null)
		{
			testConfig.logPass(what, actual);
			return;
		}
		
		if (actual != null)
		{
			if (!actual.equals(expected))
				testConfig.logFail(what, expected, actual);
			else
				testConfig.logPass(what, actual);
		}
		else
		{
			testConfig.logFail(what, expected, actual);
		}
	}
	
	public static String generateRandomAlphaNumericString(int length)
	{
		Random rd = new Random();
		String aphaNumericString = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		StringBuilder sb = new StringBuilder(length);
		
		for (int i = 0; i < length; i++)
		{
			sb.append(aphaNumericString.charAt(rd.nextInt(aphaNumericString.length())));
		}
		return sb.toString();
	}
	
	public static String getCurrentDateTime(String format)
	{
		Calendar currentDate = Calendar.getInstance();
		SimpleDateFormat formatter = new SimpleDateFormat(format);
		String dateNow = formatter.format(currentDate.getTime());
		return dateNow;
	}
	
	public static String getTimeinMillSeconds()
	{
		Date date = new Date();
		long time = date.getTime();
		return String.valueOf(time);
	}
}