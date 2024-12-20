FROM node:19.5.0-bullseye

# Set the working directory
WORKDIR /usr/src/app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the application files
COPY . .

# Build the application
RUN npm run build

# Expose the development server port
EXPOSE 5173

CMD ["npm", "run", "dev"]