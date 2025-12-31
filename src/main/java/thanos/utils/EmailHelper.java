package thanos.utils;

import java.io.File;
import java.io.IOException;
import java.util.Properties;

import jakarta.activation.DataHandler;
import jakarta.activation.DataSource;
import jakarta.activation.FileDataSource;
import jakarta.mail.Authenticator;
import jakarta.mail.Message;
import jakarta.mail.PasswordAuthentication;
import jakarta.mail.Session;
import jakarta.mail.Transport;
import jakarta.mail.internet.InternetAddress;
import jakarta.mail.internet.MimeBodyPart;
import jakarta.mail.internet.MimeMessage;
import jakarta.mail.internet.MimeMultipart;


/**
 * This Class will be used for sending mail
 * @author mukesh.rajput
 */

public class EmailHelper
{
	public static enum EmailContentType
	{
		Html,
		HtmlWithAttachment
	};
	
	/**
	 * This function is used to send the Automation report emails
	 * @param sendEmailTo - Comma Separated List of Email Id to which we want to send Email
	 * @param subject - Subject/Heading of the Email
	 * @param messageContent - Content/Full message that we want to send
	 * @param emailContentType - Defines the type of Content we want to send in the Email
	 * @param attachmentFilePath - Array of file paths of all the files we need to send as attachments
	 */
	public static void sendEmail(String sendEmailTo, String subject, String messageContent, EmailContentType emailContentType, String attachmentFilePath[])
	{
		if (sendEmailTo == null)
		{
			System.out.println("Email not sent, as email id is blank!");
		}
		else
		{
			boolean isEmailSent = false;
			String reportName = "Thanos - QA Automation";
			String username = CommonUtilities.decryptMessage(System.getProperty("thanosGmailUsername").getBytes());
			String password = CommonUtilities.decryptMessage(System.getProperty("thanosGmailPassword").getBytes());
			
			Properties properties = new Properties();
			properties.put("mail.smtp.auth", "true");
			properties.put("mail.smtp.starttls.enable", "true");
			properties.put("mail.smtp.host", "smtp.gmail.com");
			properties.put("mail.smtp.ssl.trust", "smtp.gmail.com");
			properties.put("mail.smtp.port", "587");
			Session session = Session.getInstance(properties, new Authenticator()
			{
				protected PasswordAuthentication getPasswordAuthentication()
				{
					return new PasswordAuthentication(username, password);
				}
			});
			
			try
			{
				Message message = new MimeMessage(session);
				message.setFrom(new InternetAddress(username, reportName));
				message.setReplyTo(InternetAddress.parse(sendEmailTo));
				message.addRecipients(Message.RecipientType.TO, InternetAddress.parse(sendEmailTo));
				message.setSubject(subject);
				
				switch (emailContentType)
				{
				case Html:
					message.setContent(messageContent, "text/html; charset=UTF-8");
					break;
				case HtmlWithAttachment:
					if (attachmentFilePath != null)
					{
						MimeMultipart multipart = new MimeMultipart("related");
						MimeBodyPart messageBodyPart = new MimeBodyPart();
						messageBodyPart.setContent(messageContent, "text/html; charset=UTF-8");
						multipart.addBodyPart(messageBodyPart);
						for (int i = 0; i < attachmentFilePath.length; i++)
						{
							int index = attachmentFilePath[i].lastIndexOf(File.separator);
							String fileName = attachmentFilePath[i].substring(index + 1);
							MimeBodyPart messageBodyPart2 = new MimeBodyPart();
							DataSource source = new FileDataSource(attachmentFilePath[i]);
							messageBodyPart2.setDataHandler(new DataHandler(source));
							messageBodyPart2.setFileName(fileName);
							multipart.addBodyPart(messageBodyPart2);
						}
						message.setContent(multipart);
					}
					else
					{
						System.out.println("Attachment File Name not specified or passed as null");
					}
					break;
				default:
					System.out.println("Email Content type not defined");
				}
				
				try
				{
					Transport.send(message);
					isEmailSent = true;
				}
				catch (Exception e)
				{
					e.printStackTrace();
					System.out.println("********** This is the Message we were trying to send via Email **********\n");
					System.out.println("Subject : " + message.getSubject());
					try
					{
						System.out.println("Message : " + message.getContent().toString());
					}
					catch (IOException e1)
					{
					}
				}
			}
			catch (Exception ex)
			{
				ex.printStackTrace();
			}
			
			if (isEmailSent)
				System.out.println(reportName + " sent to Email : " + sendEmailTo);
			else
				System.out.println("Email sending failed for : " + sendEmailTo);
		}
	}
}