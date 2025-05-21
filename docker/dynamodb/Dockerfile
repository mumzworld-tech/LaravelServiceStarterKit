# Use an official image for DynamoDB Local
FROM amazon/dynamodb-local:latest

# Set the working directory (optional, as command specifies paths)
# WORKDIR /home/dynamodblocal

# Expose the default DynamoDB Local port
EXPOSE 8000

# The command to start DynamoDB Local is typically specified in docker-compose.yml
# Default command is: java -jar DynamoDBLocal.jar -sharedDb -dbPath . 
# We will use the command in docker-compose.yml to specify the dbPath to the volume. 