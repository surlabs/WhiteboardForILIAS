# Whiteboard

## Description

The Whiteboard Repository Object plugin for ILIAS, is a collaborative online whiteboard tool, developed by SURLABS with funding from the University of Freiburg.
This plugin is freely distributed under the terms of the GNU General Public License version 3 (GPL-3.0),
a copy of which is available at https://www.gnu.org/licenses/gpl-3.0.en.html. This license allows for the free use,
modification, and distribution of this software, ensuring it remains open-source and accessible to the community.

The Whiteboard plugin uses a version the tldraw library https://tldraw.com created by Steve Ruiz, which is also open-source and distributed under its specific terms and conditions. For details on the tldraw license, please refer to https://github.com/tldraw/tldraw/blob/main/LICENSE.md.

DISCLAIMER: The developers, contributors, and funding entities associated with the Whiteboard plugin or the tldraw library assume no responsibility for any damages or losses incurred from the use of this software. Users are encouraged to review the license agreements and comply with the terms and conditions set forth.

Community involvement is welcome. To report bugs, suggest improvements, or participate in discussions, please visit the Mantis system and search for ILIAS Plugins under the "Whiteboard" category at https://mantis.ilias.de.

For further information, documentation, and the source code, visit our GitHub repository at https://github.com/surlabs/Whiteboard.

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

## Bug reports

To report bugs, suggest improvements, or participate in discussions, please visit the Mantis system and search for ILIAS Plugins under the "Whiteboard" category at https://mantis.ilias.de.