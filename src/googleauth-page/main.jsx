import { createRoot, render, StrictMode, createInterpolateElement } from '@wordpress/element';
import { Button, TextControl } from '@wordpress/components';
import { useEffect, useState } from 'react'; // Import useState hook for managing state
import axios from 'axios'; // Import axios for making HTTP requests
import { __ } from '@wordpress/i18n';
import { BrowserRouter as Router, Routes, Route,useLocation } from 'react-router-dom';
import ScanPosts from '../components/ScanPost';

import "./scss/style.scss"

const domElement = document.getElementById( window.wpmudevPluginTest.dom_element_id );

const WPMUDEV_PluginTest = () => {

    const [clientId, setClientId] = useState(''); // State for Client ID
    const [clientSecret, setClientSecret] = useState(''); // State for Client Secret

    const handleChangeClientId = (newValue) => {
        setClientId(newValue); // Update clientId state
    }
    const handleChangeClientSecret = (event) => {
        setClientSecret(event.target.value); // Update clientSecret state
    }

    const handleClick = () => {
        // Make sure both clientId and clientSecret are not empty
        if (!clientId || !clientSecret) {
            alert('Please enter both Client ID and Client Secret.');
            return;
        }

        // Make a POST request to the REST API endpoint
        axios.post('/wp-json/wpmudev/v1/auth/auth-url',
            {
                client_id: clientId,
                client_secret: clientSecret,
            },
            {
            headers :{
                'content-type':'application/json',
                'X-WP-NONCE':window.wpmudevPluginTest.nonce,
            } // Include cookies in the request (adjust as needed)
        })
            .then(response => {
                alert('Credentials saved successfully!');
                // Optionally, you can handle additional logic after successful save
            })
            .catch(error => {
                alert('Failed to save credentials. Please try again.');
                console.error('Error:', error);
            });
    }
    useEffect( ()=> {
        axios.get('/wp-json/wpmudev/v1/auth/auth-url',{
            headers :{
                'content-type':'application/json',
                'X-WP-NONCE':window.wpmudevPluginTest.nonce,
            } 
        })
        .then((res) => {
            setClientId(res.data.client_id);
            setClientSecret(res.data.client_secret);
        })
    },[] );
    return (
    <>
        <div class="sui-header">
            <h1 class="sui-header-title">
                Settings
            </h1>
      </div>

        <div className="sui-box">

            <div className="sui-box-header">
                <h2 className="sui-box-title">Set Google credentials</h2>
            </div>

            <div className="sui-box-body">
                <div className="sui-box-settings-row">
                    <TextControl
                        help={createInterpolateElement(
                            'You can get Client ID from <a>here</a>.',
                            {
                              a: <a href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
                            }
                          )}
                        label="Client ID"
                        value={clientId}
                        onChange={handleChangeClientId}
                    />
                </div>

                <div className="sui-box-settings-row">
                    <div className='components-base-control'>
                    <label className='components-base-control__label'>Client Secret</label>
                     <input type="password" name="Client Secret" id="client_secret" className='components-text-control__input'
                      value={clientSecret}
                      onChange={handleChangeClientSecret}
                      />
                      <p className='components-base-control__help'>{createInterpolateElement(
                            'You can get Client Secret from <a>here</a>.',
                            {
                              a: <a href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
                            }
                          )}</p>
                    </div>
                 
                </div>

                <div className="sui-box-settings-row">
                    <span>Please use this url <em>{window.wpmudevPluginTest.returnUrl}</em> in your Google API's <strong>Authorized redirect URIs</strong> field</span>
                </div>
            </div>

            <div className="sui-box-footer">
                <div className="sui-actions-right">
                    <Button
                        variant="primary"
                        onClick={ handleClick }
                    >
                        Save
                    </Button>

                </div>
            </div>

        </div>

    </>
    );
}
function useQuery() {
    const { search } = useLocation();
  
    return React.useMemo(() => new URLSearchParams(search), [search]);
  }
const App = () => (
    <Router>
    <QueryParamsDemo />
  </Router>    
);
function QueryParamsDemo() {
    let query = useQuery();
    const pages = query.get("page");
    if(pages=='wpmudev_plugintest_auth'){
        return (<WPMUDEV_PluginTest />);
    }
    if(pages=='wpmudev-posts-maintenance'){
        return (<ScanPosts />);
    }
}

if ( createRoot ) {
    createRoot( domElement ).render(<StrictMode><App /></StrictMode>);
   
} else {
    render( <StrictMode><App /></StrictMode>, domElement );
   
}
