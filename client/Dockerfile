# Use an official node image as a base
FROM node

# Set working directory
WORKDIR /app

# Install dependencies
COPY package.json ./
RUN npm install

# Copy the project files
COPY . .

# Build the React app
RUN npm run build

# Start the app
CMD ["npm", "start"]

EXPOSE 3000
