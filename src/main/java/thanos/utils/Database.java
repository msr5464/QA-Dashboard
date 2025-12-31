package thanos.utils;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

public class Database
{
	private static Object thanosConnection = null;
	
	public enum DatabaseName
	{
		QA_Dashbaord("thanos");
		
		public final String keyIdentifier;
		
		DatabaseName(final String value)
		{
			keyIdentifier = value;
		}
	}
	
	public enum QueryType
	{
		select,
		update,
		delete
	}
	
	/**
	 * Creates database connection using the Config parameters - 'DatabaseString', 'DatabaseUsername' and 'DatabasePassword'
	 * @param Config test config instance
	 * @param DatabaseName - name of database to be connected
	 * @return Database Connection
	 */
	public static Object getConnection(Config testConfig, DatabaseName databaseName)
	{
		Object connection = null;
		try
		{
			synchronized (Database.class)
			{
				switch (databaseName)
				{
				case QA_Dashbaord:
					connection = thanosConnection;
					if (connection == null)
					{
						Class.forName("com.mysql.cj.jdbc.Driver");
						connection = createConnection(testConfig, databaseName);
						thanosConnection = connection;
					}
					break;
				}
				if (Config.isDebugMode)
					testConfig.logComment("DB Connection succeeded");
			}
		}
		catch (ClassNotFoundException e)
		{
			testConfig.logExceptionAndFail(e);
		}
		return connection;
	}
	
	private static Connection createConnection(Config testConfig, DatabaseName databaseName)
	{
		try
		{
			String host = testConfig.getRunTimeProperty(databaseName.keyIdentifier + "DatabaseHost");
			String userName = testConfig.getRunTimeProperty(databaseName.keyIdentifier + "DatabaseUsername");
			String password = testConfig.getRunTimeProperty(databaseName.keyIdentifier + "DatabasePassword");
			testConfig.logComment("Connecting to " + databaseName.toString() + ":-" + host);
			return DriverManager.getConnection(host, userName, password);
		}
		catch (SQLException e)
		{
			testConfig.logExceptionAndFail(e);
			return null;
		}
	}
	
	public static Object executeQuery(Config testConfig, String sqlQuery, QueryType queryType, DatabaseName databaseName)
	{
		sqlQuery = testConfig.replaceArgumentsWithRunTimeProperties(sqlQuery);
		testConfig.logCommentForDebugging("Executing query - '" + sqlQuery + "'");
		Connection connection = (Connection) getConnection(testConfig, databaseName);
		Object returnValue = null;
		try
		{
			switch (queryType)
			{
			case select:
				ResultSet resultSet = connection.createStatement().executeQuery(sqlQuery);
				if (null == resultSet)
					testConfig.logWarning("No data was returned for above query");
				returnValue = resultSet;
				break;
			case update:
				int recordsModified = connection.createStatement().executeUpdate(sqlQuery);
				if (recordsModified == 0)
					testConfig.logWarning("No record updated for this query");
				else
					testConfig.logCommentForDebugging("Total record updated - " + recordsModified);
				returnValue = recordsModified;
				break;
			case delete:
				returnValue = connection.createStatement().executeUpdate(sqlQuery);
				testConfig.logCommentForDebugging("Total records deleted - " + returnValue);
				break;
			}
		}
		catch (SQLException e)
		{
			testConfig.logExceptionAndFail(e);
		}
		return returnValue;
	}
	
	public static Map<String, String> executeSelectQuery(Config testConfig, String query, DatabaseName databaseName)
	{
		int rowNumber = 1;
		Map<String, String> resultMap = null;
		ResultSet resultSet = (ResultSet) executeQuery(testConfig, query, QueryType.select, databaseName);
		
		int row = 1;
		try
		{
			while (resultSet.next())
			{
				if (row == rowNumber)
				{
					resultMap = createHashMapFromResultSet(testConfig, resultSet);
					testConfig.logCommentForDebugging("Query Result :- " + resultMap.toString());
					break;
				}
				else
				{
					row++;
				}
			}
		}
		catch (SQLException e)
		{
			testConfig.logExceptionAndFail(e);
		}
		return resultMap;
	}
	
	public static Map<String, String> createHashMapFromResultSet(Config testConfig, ResultSet resultSet)
	{
		HashMap<String, String> mapData = new HashMap<String, String>();
		
		try
		{
			ResultSetMetaData meta = resultSet.getMetaData();
			for (int col = 1; col <= meta.getColumnCount(); col++)
			{
				try
				{
					String columnName = meta.getColumnLabel(col);
					String columnValue = resultSet.getObject(col).toString();
					
					// Code to handle TINYINT case
					if (meta.getColumnTypeName(col).equalsIgnoreCase("TINYINT"))
						columnValue = Integer.toString(resultSet.getInt(col));
					
					mapData.put(columnName, columnValue);
				}
				catch (Exception e)
				{
					mapData.put(meta.getColumnLabel(col), "");
				}
			}
		}
		catch (SQLException e)
		{
			testConfig.logExceptionAndFail(e);
		}
		return mapData;
	}
}