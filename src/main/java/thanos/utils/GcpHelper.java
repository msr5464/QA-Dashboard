package thanos.utils;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.regex.Matcher;
import org.apache.commons.io.IOUtils;
import com.google.api.gax.paging.Page;
import com.google.auth.oauth2.ServiceAccountCredentials;
import com.google.cloud.storage.Blob;
import com.google.cloud.storage.BlobId;
import com.google.cloud.storage.BlobInfo;
import com.google.cloud.storage.CopyWriter;
import com.google.cloud.storage.Storage;
import com.google.cloud.storage.Storage.BlobListOption;
import com.google.cloud.storage.StorageOptions;

public class GcpHelper
{
	private static Storage storage = null;
	
	private static Storage connectToGcp(Config testConfig, String authKey)
	{
		if (storage == null)
		{
			try
			{
				testConfig.logComment("Connecting to GCP server...");
				storage = StorageOptions.newBuilder().setCredentials(ServiceAccountCredentials.fromStream(new FileInputStream(authKey))).build().getService();
				testConfig.logCommentForDebugging("Connected successfully with GCP server.");
			}
			catch (Exception e)
			{
				testConfig.logExceptionAndFail("Unable to connect with GCP", e);
			}
		}
		return storage;
	}
	
	public static String uploadFileInGcpBucket(Config testConfig, String authKey, String bucketName, String filePath, String updatedFileName)
	{
		String uploadedFileUrl = null;
		storage = GcpHelper.connectToGcp(testConfig, authKey);
		try
		{
			testConfig.logComment("Uploading file to GCP bucket : " + updatedFileName);
			FileInputStream fileInputStream = new FileInputStream(new File(filePath));
			testConfig.logComment("Total file size to upload (in bytes) : " + fileInputStream.available());
			byte[] fileContent = IOUtils.toByteArray(fileInputStream);
			BlobInfo blobInfo = storage.create(BlobInfo.newBuilder(bucketName, updatedFileName).build(), fileContent);
			// BlobInfo blobInfo = storage.create(BlobInfo.newBuilder(bucketName, updatedFileName).setAcl(new ArrayList<>(Arrays.asList(Acl.of(User.ofAllUsers(), Role.READER)))).build(), fileContent);
			uploadedFileUrl = blobInfo.getMediaLink();
			testConfig.logComment("File uploaded at : " + uploadedFileUrl);
		}
		catch (Exception e)
		{
			testConfig.logExceptionAndFail("Unable to upload file to GCP bucket", e);
		}
		return uploadedFileUrl;
	}
	
	public static void deleteFileFromGcpBucket(Config testConfig, String authKey, String bucketName, String fileNameInBucket)
	{
		storage = GcpHelper.connectToGcp(testConfig, authKey);
		testConfig.logCommentForDebugging("Deleting file from GCP bucket : " + fileNameInBucket);
		
		boolean isDeleted = storage.delete(BlobId.of(bucketName, fileNameInBucket));
		
		if (isDeleted)
			testConfig.logComment("Given File deleted now : " + fileNameInBucket);
		else
			testConfig.logFail("Unable to delete this file from GCP Bucket : " + fileNameInBucket);
	}
	
	public static String downloadFileFromGcpBucket(Config testConfig, String authKey, String bucketName, String fileNameInBucket, String localfilePath)
	{
		localfilePath = localfilePath.replaceAll("[/\\\\]+", Matcher.quoteReplacement(File.separator));
		
		storage = GcpHelper.connectToGcp(testConfig, authKey);
		testConfig.logCommentForDebugging("Downloading file from GCP bucket : " + fileNameInBucket);
		
		Blob fileAsBlob = storage.get(BlobId.of(bucketName, fileNameInBucket));
		testConfig.logComment("Total file size to download (in bytes) : " + fileAsBlob.getSize());
		CommonUtilities.createFolder(localfilePath.substring(0, localfilePath.lastIndexOf(File.separator)));
		
		try
		{
			fileAsBlob.downloadTo(new File(localfilePath).toPath());
		}
		catch (Exception e)
		{
			testConfig.logWarning("Unable to download in first attempt, so trying again...");
			fileAsBlob.downloadTo(new File(localfilePath).toPath());
		}
		
		if (new File(localfilePath).exists())
			testConfig.logComment("Given file downloaded at : " + localfilePath);
		else
			testConfig.logFail("Unable to download the file from GCP Bucket with name : " + fileNameInBucket);
		
		return localfilePath;
	}
	
	/**
	 * Creates a text file and upload to gcp bucket.
	 * @param testConfig the test config
	 * @param downloadFileNames List of fileNames, to be written in new file
	 * @param commonPathForFileUploadAndDownload: the common path for new file creation and list of input files
	 * @param bucketName: gcp bucket name for file upload
	 */
	public void createAndUploadProcessedFileOnGcp(Config testConfig, String authKey, List<String> downloadFileNames, String commonPathForFileUploadAndDownload, String bucketName)
	{
		if (!downloadFileNames.isEmpty())
		{
			String currentTime = CommonUtilities.getTimeinMillSeconds();
			String processedFileName = "processedFileOn_" + currentTime + ".txt";
			try
			{
				FileWriter writer = new FileWriter(commonPathForFileUploadAndDownload + processedFileName);
				for (String fileName : downloadFileNames)
				{
					writer.write(fileName);
					writer.write("\n");
				}
				writer.close();
				
			}
			catch (IOException e)
			{
				testConfig.logException("Some error occured while writing file", e);
			}
			uploadFileInGcpBucket(testConfig, authKey, bucketName, commonPathForFileUploadAndDownload + processedFileName, processedFileName);
		}
	}
	
	/**
	 * Gets the list of filenames in asc order of updated on server.
	 * @param testConfig the test config
	 * @param authKey the auth key
	 * @param bucketName the bucket name
	 * @param filePrefix : prefix of file name to search files on server
	 * @return the List of fileNames in asc sorted order of updatedOn timeStamp
	 */
	public static List<String> getFilesListInAscSortedOrder(Config testConfig, String authKey, String bucketName, String filePrefix)
	{
		storage = GcpHelper.connectToGcp(testConfig, authKey);
		List<String> fileNamesToDownload = new ArrayList<String>();
		List<Long> lastUpdatedTimeStamp = new ArrayList<Long>();
		HashMap<Long, String> resultMap = new HashMap<Long, String>();
		Page<Blob> blobs = storage.list(bucketName, BlobListOption.currentDirectory(), BlobListOption.prefix(filePrefix));
		Iterable<Blob> blobIterator = blobs.iterateAll();
		
		for (Blob eachBlob : blobIterator)
		{
			if (eachBlob.getUpdateTime() != null)
			{
				lastUpdatedTimeStamp.add(eachBlob.getUpdateTime());
				resultMap.put(eachBlob.getUpdateTime(), eachBlob.getName());
			}
		}
		Collections.sort(lastUpdatedTimeStamp);
		for (int i = 0; i < lastUpdatedTimeStamp.size(); i++)
		{
			fileNamesToDownload.add(resultMap.get(lastUpdatedTimeStamp.get(i)));
		}
		testConfig.logComment("Total count of file to download: " + fileNamesToDownload.size());
		return fileNamesToDownload;
	}
	
	public static Boolean downloadFiles(Config testConfig, String authKey, String bucketName, List<String> fileNamesToDownload, String localFilePath)
	{
		int fileDownloadedCount = 0;
		if (fileNamesToDownload.size() > 0)
		{
			storage = GcpHelper.connectToGcp(testConfig, authKey);
			for (String fileName : fileNamesToDownload)
			{
				testConfig.logComment("Downloading file at.. " + (localFilePath + fileName));
				storage.get(BlobId.of(bucketName, fileName)).downloadTo(new File(localFilePath + fileName).toPath());
				fileDownloadedCount++;
			}
			if (fileNamesToDownload.size() == fileDownloadedCount)
			{
				testConfig.logComment("All the files downloaded to path: " + localFilePath);
				return true;
			}
			else
			{
				testConfig.logFail("Number of files to download ", fileNamesToDownload.size(), fileDownloadedCount);
				return false;
			}
		}
		else
		{
			testConfig.logComment("No file to download ");
			return false;
		}
	}
	
	public static String fetchLastUpdatedFileFromGcpBucket(Config testConfig, String authKey, String bucketName, String internalDirectoryPath, String... partialFileNamesToMatch)
	{
		List<String> filesInSortedOrder = getFilesListInAscSortedOrder(testConfig, authKey, bucketName, internalDirectoryPath);
		String requiredFile = "";
		for (int i = filesInSortedOrder.size() - 1; i >= 0; i--)
		{
			if (partialFileNamesToMatch == null || partialFileNamesToMatch.length <= 0)
			{
				requiredFile = filesInSortedOrder.get(i);
				break;
			}
			else
			{
				int matchCounter = 0;
				for (int subString = 0; subString < partialFileNamesToMatch.length; subString++)
				{
					if (filesInSortedOrder.get(i).contains(partialFileNamesToMatch[subString]))
					{
						matchCounter++;
					}
				}
				if (matchCounter == partialFileNamesToMatch.length)
				{
					requiredFile = filesInSortedOrder.get(i);
					break;
				}
			}
		}
		if (requiredFile == null)
			testConfig.logFail("Unable to fetch the (matching) file from GCP Bucket.");
		else
			testConfig.logComment("Last updated file is : " + requiredFile);
		return requiredFile;
	}
	
	/**
	 * Rename existing file on gcp server.
	 * @param testConfig the test config
	 * @param authKey: the auth key to connect to server
	 * @param bucketName the bucket name
	 * @param fileNameToRename : file Name which need to be rename
	 * @param newFileName : new name of file.
	 * @return : the updated new fileName
	 */
	public static String renameFileOnGcp(Config testConfig, String authKey, String bucketName, String fileNameToRename, String newFileName)
	{
		storage = GcpHelper.connectToGcp(testConfig, authKey);
		return renameFile(testConfig, storage, bucketName, fileNameToRename, newFileName);
	}
	
	private static String renameFile(Config testConfig, Storage storage, String bucketName, String fileNameToRename, String newFileName)
	{
		Blob fileAsBlob = storage.get(BlobId.of(bucketName, fileNameToRename));
		testConfig.logComment("Renaming " + fileNameToRename + " to :- " + newFileName);
		CopyWriter copyWriter = fileAsBlob.copyTo(BlobId.of(bucketName, newFileName));
		fileAsBlob.delete();
		return copyWriter.getResult().getName();
	}
	
	/**
	 * Based of flag, renaming of existing files or file deletion can be done. if renaming is used with prefix 'P_'
	 * on file testFile.csv, it will become P_testFile.csv
	 * @param testConfig the test config
	 * @param authKey the auth key
	 * @param bucketName the bucket name
	 * @param fileNamesListToRename :list of fileNames to be renamed or deleted.
	 * @param wantToRename : flag which decides renaming or else deletion of file.
	 * @param prefixToAppend : If renaming is opted, prefix to new name of file.
	 * @return the list of renamed file or list of deleted files
	 */
	public static List<String> renameOrDeleteMultipleFiles(Config testConfig, String authKey, String bucketName, List<String> fileNamesListToRename, Boolean wantToRename, String prefixToAppend)
	{
		if (!fileNamesListToRename.isEmpty())
		{
			int count = 0;
			storage = GcpHelper.connectToGcp(testConfig, authKey);
			List<String> listOfNewFileNames = new ArrayList<String>();
			if (wantToRename && prefixToAppend != null)
			{
				for (String fileName : fileNamesListToRename)
					listOfNewFileNames.add(renameFile(testConfig, storage, bucketName, fileName, prefixToAppend + fileName));
				return listOfNewFileNames;
			}
			else
			{
				for (String fileName : fileNamesListToRename)
				{
					testConfig.logComment("Deleting file : " + fileName);
					storage.get(BlobId.of(bucketName, fileName)).delete();
					count++;
				}
				CommonUtilities.compareEquals(testConfig, "Count of file Deleted", fileNamesListToRename.size(), count);
				return fileNamesListToRename;
			}
			
		}
		else
			return null;
	}
	
}