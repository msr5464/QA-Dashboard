package thanos.models;

/**
 * Unified Bug Data Model
 * Represents a bug that can be from STG, FCT, or PRD environment
 */
public class BugData {

    // Enums for categorization
    public enum BugCategory {
        STG("STG"),
        FCT("FCT"),
        PRD("PRD");

        private String value;

        BugCategory(String value) {
            this.value = value;
        }

        public String getValue() {
            return value;
        }
    }

    public enum Classification {
        PAYMENTGATEWAY("PaymentGateway"),
        PARTNER("Partner"),
        OTHERS("Others"),
        INVALID("Invalid");

        private String value;

        Classification(String value) {
            this.value = value;
        }

        public String getValue() {
            return value;
        }
    }

    // Core identification
    private String issueId;
    private String createdAt;
    private String updatedAt;
    private BugCategory bugCategory;
    private Classification classification;

    // Organization
    private String teamName;
    private String projectName;
    private String verticalName;
    private String productArea;

    // Bug details
    private String priority;
    private String status;
    private String bugType;
    private String rootCause;
    private String bugPlatform;
    private String title;
    private String environment;
    private String bugFoundBy;
    private String version;

    // Timing metrics (nullable for some categories)
    private Integer devTime;
    private Integer qaTime;
    private Integer overallTime;
    private Integer pmTime;
    private Integer developmentTime;

    // Flags
    private int isInvalid;
    private int isDeleted;

    // Constructors
    public BugData() {
        this.isInvalid = 0;
        this.isDeleted = 0;
    }

    // Getters and Setters
    public String getIssueId() {
        return issueId;
    }

    public void setIssueId(String issueId) {
        this.issueId = issueId;
    }

    public String getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(String createdAt) {
        this.createdAt = createdAt;
    }

    public String getUpdatedAt() {
        return updatedAt;
    }

    public void setUpdatedAt(String updatedAt) {
        this.updatedAt = updatedAt;
    }

    public BugCategory getBugCategory() {
        return bugCategory;
    }

    public void setBugCategory(BugCategory bugCategory) {
        this.bugCategory = bugCategory;
    }

    public Classification getClassification() {
        return classification;
    }

    public void setClassification(Classification classification) {
        this.classification = classification;
    }

    public String getTeamName() {
        return teamName;
    }

    public void setTeamName(String teamName) {
        this.teamName = teamName;
    }

    public String getProjectName() {
        return projectName;
    }

    public void setProjectName(String projectName) {
        this.projectName = projectName;
    }

    public String getVerticalName() {
        return verticalName;
    }

    public void setVerticalName(String verticalName) {
        this.verticalName = verticalName;
    }

    public String getProductArea() {
        return productArea;
    }

    public void setProductArea(String productArea) {
        this.productArea = productArea;
    }

    public String getPriority() {
        return priority;
    }

    public void setPriority(String priority) {
        this.priority = priority;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getBugType() {
        return bugType;
    }

    public void setBugType(String bugType) {
        this.bugType = bugType;
    }

    public String getRootCause() {
        return rootCause;
    }

    public void setRootCause(String rootCause) {
        this.rootCause = rootCause;
    }

    public String getBugPlatform() {
        return bugPlatform;
    }

    public void setBugPlatform(String bugPlatform) {
        this.bugPlatform = bugPlatform;
    }

    public String getTitle() {
        return title;
    }

    public void setTitle(String title) {
        this.title = title;
    }

    public String getEnvironment() {
        return environment;
    }

    public void setEnvironment(String environment) {
        this.environment = environment;
    }

    public String getBugFoundBy() {
        return bugFoundBy;
    }

    public void setBugFoundBy(String bugFoundBy) {
        this.bugFoundBy = bugFoundBy;
    }

    public String getVersion() {
        return version;
    }

    public void setVersion(String version) {
        this.version = version;
    }

    public Integer getDevTime() {
        return devTime;
    }

    public void setDevTime(Integer devTime) {
        this.devTime = devTime;
    }

    public Integer getQaTime() {
        return qaTime;
    }

    public void setQaTime(Integer qaTime) {
        this.qaTime = qaTime;
    }

    public Integer getOverallTime() {
        return overallTime;
    }

    public void setOverallTime(Integer overallTime) {
        this.overallTime = overallTime;
    }

    public Integer getPmTime() {
        return pmTime;
    }

    public void setPmTime(Integer pmTime) {
        this.pmTime = pmTime;
    }

    public Integer getDevelopmentTime() {
        return developmentTime;
    }

    public void setDevelopmentTime(Integer developmentTime) {
        this.developmentTime = developmentTime;
    }

    public int getIsInvalid() {
        return isInvalid;
    }

    public void setIsInvalid(int isInvalid) {
        this.isInvalid = isInvalid;
    }

    public int getIsDeleted() {
        return isDeleted;
    }

    public void setIsDeleted(int isDeleted) {
        this.isDeleted = isDeleted;
    }

    // Utility methods

    /**
     * Should this bug be inserted into the database?
     * Currently inserts all bugs with proper classification
     */
    public boolean shouldInsert() {
        return true;
    }

    /**
     * Get classification as string for database
     */
    public String getClassificationString() {
        return classification != null ? classification.getValue() : "PaymentGateway";
    }

    /**
     * Get category as string for database
     */
    public String getCategoryString() {
        return bugCategory != null ? bugCategory.getValue() : "STG";
    }

    @Override
    public String toString() {
        return "BugData{" +
                "issueId='" + issueId + '\'' +
                ", category=" + bugCategory +
                ", classification=" + classification +
                ", teamName='" + teamName + '\'' +
                ", vertical='" + verticalName + '\'' +
                ", priority='" + priority + '\'' +
                '}';
    }
}
