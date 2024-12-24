<div class="hidden_librenms_logged_in">
    @include('webapp.librenms._librenms_token')
</div>

<div class="card hidden_librenms_logged_out">
    <div class="card-header">Rainbow LibreNMS Dashboard</div>
    <div class="card-body">
        <button id="refreshDevicesBtn" class="btn btn-primary mb-3">Refresh Device Status</button>
        <div id="deviceList">
            <!-- Devices will be dynamically inserted here -->
        </div>
    </div>
</div>

<script>
    async function fetchAndDisplayDevices(token) {
        try {
            const response = await axios.get('https://nms.rainbowtel.net/api/v0/devices', {
                headers: {
                    'X-Auth-Token': token,
                    'Accept': 'application/json',
                }
            });




            const devices = response.data.devices;
            const deviceList = document.getElementById('deviceList');
            deviceList.innerHTML = ''; // Clear the list before populating

            console.log('Devices List',devices);



            for (let device of devices) {
                const deviceItem = document.createElement('div');
                deviceItem.classList.add('device-item');


                console.log(device);
                // Status indicator based on ping status
                const statusIndicator = document.createElement('span');
                statusIndicator.classList.add('status-indicator');
                statusIndicator.style.marginRight = '10px';

                if (!device.ignore_status) {
                    if (device.status === 1) {
                        statusIndicator.style.color = 'green'; // Online
                        statusIndicator.textContent = '🟢';
                    } else {
                        statusIndicator.style.color = 'red'; // Offline
                        statusIndicator.textContent = '🔴';
                    }
                } else {
                    statusIndicator.style.color = 'grey'; // Ping disabled
                    statusIndicator.textContent = '⚪';
                }

                // Device information
                const deviceInfo = document.createElement('span');

                deviceInfo.innerHTML = `<strong>${device.hostname}</strong> (${device.sysName || 'No System Name'})`;

                statusIndicator.appendChild(deviceInfo);

                deviceItem.appendChild(statusIndicator);

                // Fetch services for this device
                const servicesResponse = await axios.get(`https://nms.rainbowtel.net/api/v0/services/${device.device_id}`, {
                    headers: {
                        'X-Auth-Token': token,
                        'Accept': 'application/json',
                    }
                });

                const services = servicesResponse.data.services;


                console.log(services);


                if (services.length > 0) {
                    const servicesList = document.createElement('ul');
                    servicesList.style.marginLeft = '20px'; // Indent services under the device

                    for (let service of services) {

                        for (let serve of service){
                            console.log('servuice loop',serve);

                            const serviceItem = document.createElement('li');
                            serviceItem.textContent = `${serve.service_name} - ${serve.service_type} - ${serve.service_status === 0 ? 'Up' : 'Down'}`;
                            serviceItem.style.color = serve.service_status === 0 ? 'green' : 'red';
                            servicesList.appendChild(serviceItem);
                        }



                    }



                    deviceItem.appendChild(servicesList);
                }

                deviceList.appendChild(deviceItem);
            }

        } catch (error) {
            console.error('Error fetching devices:', error);
            showAlert('Failed to fetch devices from LibreNMS.', 'danger');
        }
    }

    // Event listener for the refresh button
    document.getElementById('refreshDevicesBtn').addEventListener('click', function () {
        const token = appState.tokens.libreNMS;
        if (token) {
            fetchAndDisplayDevices(token);
        } else {
            showAlert('No LibreNMS token found. Please log in.', 'danger');
        }
    });

    // Rainbow Dashboard Login Form Submission
    document.getElementById('libreNMSTokenForm').addEventListener('submit', async (e) => {
        e.preventDefault();



        //const libreNMSToken = appState.tokens.libreNMS;
        const libreNMSToken = document.getElementById('libreNMSToken').value;


        console.log(libreNMSToken);

        alert('librenms login' + libreNMSToken);

        try {
            const response = await axios.get('https://nms.rainbowtel.net/api/v0/system', {
                headers: {
                    'X-Auth-Token': libreNMSToken,
                    'Accept': 'application/json',
                }
            });

            appState.tokens.libreNMS = libreNMSToken;
            localStorage.setItem('app_state', JSON.stringify(appState));

            showAlert('LibreNMS token uploaded successfully!');

            location.reload();


            //updateUserUI();

        } catch (error) {
            console.error('Error:', error);
            showAlert('Failed to connect with token to Rainbow LibreNMS. Please check your credentials.', 'danger');
        }
    });

</script>
