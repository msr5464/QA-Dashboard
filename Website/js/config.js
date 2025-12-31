// Project-specific configuration for custom group names
// This configuration defines which projects use custom 4-group setup vs standard 3-group setup

const projectConfig = {
    // Projects with custom 4-group configuration (webCases, apiCases, androidCases, iosCases)
    customGroupProjects: {
        "TreasuryTeam3": {
            environmentAndGroupNamePairs: [
                "staging,webCases",
                "staging,apiCases",
                "staging,androidCases",
                "staging,iosCases"
            ]
        },
        "CardsInfra": {
            environmentAndGroupNamePairs: [
                "staging,webCases",
                "staging,apiCases",
                "staging,androidCases", 
                "staging,iosCases"
            ]
        }
    },
    
    // Default configuration for projects without custom groups (regression, androidCases, iosCases)
    defaultEnvironmentAndGroupNamePairs: [
        "staging,regression",
        "staging,androidCases",
        "staging,iosCases"
    ]
};

/**
 * Get environment and group name pairs for a specific project
 * @param {string} projectName - The name of the project
 * @returns {Array} Array of environment and group name pairs
 */
function getEnvironmentAndGroupNamePairs(projectName) {
    // Check if project has custom group configuration
    if (projectConfig.customGroupProjects[projectName]) {
        console.log(`Using custom groups for project: ${projectName}`);
        return projectConfig.customGroupProjects[projectName].environmentAndGroupNamePairs;
    }
    
    // Use default configuration
    console.log(`Using default groups for project: ${projectName}`);
    return projectConfig.defaultEnvironmentAndGroupNamePairs;
}

/**
 * Check if a project uses custom groups (4 groups instead of 3)
 * @param {string} projectName - The name of the project
 * @returns {boolean} True if project uses custom groups, false otherwise
 */
function isCustomGroupProject(projectName) {
    return projectConfig.customGroupProjects.hasOwnProperty(projectName);
}

/**
 * Get all custom group projects
 * @returns {Object} Object containing all custom group projects
 */
function getCustomGroupProjects() {
    return projectConfig.customGroupProjects;
}

/**
 * Get group display names for UI
 * @param {string} groupName - The technical group name
 * @returns {string} Display name for the group
 */
function getGroupDisplayName(groupName) {
    const displayNames = {
        'webCases': 'Web Cases',
        'apiCases': 'API Cases', 
        'androidCases': 'Android Cases',
        'iosCases': 'iOS Cases',
        'regression': 'Regression'
    };
    
    return displayNames[groupName] || groupName;
} 