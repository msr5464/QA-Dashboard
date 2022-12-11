package thanos.utils;

import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

/**
 * This class contains all the functions needed to read write the TestData from excel sheets
 * @author MukeshR
 */
public class TestDataReader
{
	
	private ArrayList<List<String>> testData;
	
	public TestDataReader(Config testConfig, String sheetName, String excelFilePath)
	{
		readFile(testConfig, sheetName, excelFilePath);
	}
	
	/**
	 * This function is used to read the excel sheets of type .xls, .xlsx and .csv
	 * @param sheetName
	 * @param pathOfExcelFile
	 */
	private void readFile(Config testConfig, String sheetName, String pathOfExcelFile)
	{
		String filename = pathOfExcelFile.trim();
		BufferedReader csvFile = null;
		FileInputStream fileInputStream = null;
		testData = new ArrayList<List<String>>();
		testConfig.logComment("Read:-'" + pathOfExcelFile + "', Sheet:- '" + sheetName + "'");
		try
		{
			fileInputStream = new FileInputStream(filename);
			if (filename.endsWith(".csv"))
			{
				csvFile = new BufferedReader(new FileReader(pathOfExcelFile));
				String dataRow = csvFile.readLine();
				
				while (dataRow != null)
				{
					String[] dataArray = dataRow.split(",");
					List<String> data = new ArrayList<String>();
					for (int z = 0; z < dataArray.length; z++)
					{
						String str = dataArray[z];
						if (str.startsWith("\""))
							str = str.replace("\"", "");
						data.add(str);
					}
					testData.add(data);
					dataRow = csvFile.readLine();
				}
			}
		}
		catch (FileNotFoundException e)
		{
			testConfig.logExceptionAndFail("", e);
		}
		catch (IOException e)
		{
			testConfig.logExceptionAndFail("", e);
		}
		catch (Exception e)
		{
			testConfig.logExceptionAndFail("", e);
		}
		finally
		{
			if (fileInputStream != null)
			{
				try
				{
					fileInputStream.close();
				}
				catch (IOException e)
				{
					testConfig.logExceptionAndFail("", e);
				}
			}
			
			if (csvFile != null)
			{
				try
				{
					csvFile.close();
				}
				catch (IOException e)
				{
					testConfig.logExceptionAndFail("", e);
				}
			}
		}
	}
	
	/**
	 * This function is used to fetch the data of a particular 'cell' of excel sheet
	 * @param testConfig TODO
	 * @param row
	 * @param column
	 * @return
	 */
	public String getData(Config testConfig, int row, String column)
	{
		String data = "";
		List<String> headerRow = testData.get(0);
		List<String> dataRow = testData.get(row);
		
		for (int i = 0; i < headerRow.size(); i++)
		{
			if (headerRow.get(i).equalsIgnoreCase(column))
			{
				try
				{
					data = dataRow.get(i);
				}
				catch (IndexOutOfBoundsException e)
				{
					data = "";
				}
				break;
			}
		}
		
		if (data.equals(""))
		{
			data = "{skip}";
		}
		else
		{
			if (data.contains("{empty}"))
				data = data.replace("{empty}", "");
			if (data.contains("{space}"))
				data = data.replace("{space}", " ");
			if (data.contains("{time}"))
			{
				data = data.replace("{time}", CommonUtilities.getCurrentDateTime("YYYY-MM-dd hh:mm:ss ZZZZ"));
			}
		}
		if (Config.isDebugMode)
			testConfig.logComment("Value of '" + column + "' column at row " + row + " is:- '" + data + "'");
		return data;
	}
	
	/**
	 * This method returns the number of records present in the datasheet
	 * @return number of records
	 */
	public int getRecordsNum()
	{
		return testData.size();
	}
	
	/**
	 * This method returns the number of columns of the datasheet
	 * (It counts the header and returns the number)
	 * @return number of columns
	 */
	public int getColumnNum()
	{
		List<String> headerRow = testData.get(0);
		return headerRow.size();
	}
	
	/**
	 * Returns the Excel header value
	 * @param row - Excel Row number to read
	 * @return The value read
	 */
	public String getHeaderData(int rowNumber)
	{
		String data = "";
		List<String> dataRow = testData.get(0);
		try
		{
			data = dataRow.get(rowNumber);
		}
		catch (IndexOutOfBoundsException e)
		{
			data = "";
		}
		data = data.trim();
		if (data.equals(""))
		{
			data = "{skip}";
			return data;
		}
		if (data.contains("{empty}"))
		{
			data = data.replace("{empty}", "");
		}
		if (data.contains("{space}"))
		{
			data = data.replace("{space}", " ");
		}
		return data;
	}
	
	public HashMap<String, String> getTestData(Config testConfig, int testDataRowNo)
	{
		
		HashMap<String, String> testDataMap = new HashMap<String, String>();
		
		for (int i = 0; i < testData.get(0).size(); i++)
		{
			testDataMap.put(testData.get(0).get(i), getData(testConfig, testDataRowNo, testData.get(0).get(i)));
		}
		return testDataMap;
	}
}