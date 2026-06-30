$(document).ready(function(){

    axios.get(base_url+'balance-info-admin')
    .then(function(response) {
        const data = response.data.data;
        const data_result = response.data.result;

        if(data_result){

        const count2 = new countUp.CountUp("bsc_lending_token");
        const count3 = new countUp.CountUp("bsc_lending_currency");
        count2.update(data.bsc_lending_token);
        count3.update(data.bsc_lending_currency);
        
        
        } 

    })
    .catch(function(error) {
        console.error('Error fetching data:', error);
    });


    });