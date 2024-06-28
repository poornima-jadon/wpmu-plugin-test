import React from 'react';
import axios from 'axios';

const ScanPosts = () => {
    const scanPosts = async () => {
        try {
            await axios.get('/wp-json/wpmudev/v1/scan-posts');
            alert('Posts scanned successfully!');
        } catch (error) {
            console.error('Error scanning posts:', error);
            alert('Failed to scan posts. Please try again.');
        }
    };

    return (
        <div>
            <button onClick={scanPosts}>Scan Posts</button>
        </div>
    );
};

export default ScanPosts;
