# Whiteboard

## Description

Whiteboard is a collaborative online whiteboard solution, designed to integrate seamlessly with Ilias through a custom plugin developed by Surlabs.

## Installation

### Step 1: Clone the Plugin

To install the Whiteboard plugin, start by creating the necessary directory structure in your Ilias installation and clone the repository:

1. Create the directory structure:

```bash
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
```

2. Change to the created directory:

```bash
cd Customizing/global/plugins/Services/Repository/RepositoryObject
```

3. Clone the Whiteboard plugin repository:

```bash
git clone https://github.com/surlabs/Whiteboard.git Whiteboard
```

### Step 2: Install Dependencies

Navigate to the base directory of your domain where Ilias is installed, and run Composer to install the necessary dependencies for the Whiteboard plugin:

1. From the base directory of your domain, run:

```bash
composer install -vvv
```

### Step 3: Install and Activate the Plugin in Ilias

1. In your Ilias administration panel, go to **Administration > Extending Ilias > Plugins**.
2. Locate the "Whiteboard" plugin in the list, install it, and then activate it.

### Step 4: Configure the Plugin

1. After activating the plugin, click on the "Configure" button next to the Whiteboard plugin listing.
2. In the configuration settings, specify the URL of your WebSocket server. This is crucial for the Whiteboard plugin to communicate with the WebSocket server for real-time collaboration features.

## WebSocket Server Setup

The Whiteboard plugin requires a running instance of the Whiteboard WebSocket Server for real-time collaboration. Follow the instructions provided in the WebSocket server's repository to set it up:

[Whiteboard WebSocket Server Setup Instructions](https://github.com/surlabs/whiteboard-websocket-server)

Ensure the WebSocket server is running and accessible before configuring the Whiteboard plugin in Ilias.

## Usage

After installation and configuration, the Whiteboard plugin allows users to create and collaborate on whiteboards within the Ilias platform. Users can access the whiteboard feature through the plugin's interface in their courses or groups.

## Support

For support, please create an issue in the GitHub repository of the Whiteboard plugin or contact Surlabs directly.