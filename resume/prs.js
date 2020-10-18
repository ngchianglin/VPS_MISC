(function()
{

  const debug = false;
  const REFRESH_TIME = 1000 * 60; 
  const URL_PATH = "/resume/";
  const MAIN_URL = "https://" + window.location.hostname + URL_PATH;
  const REFRESH_URL = "https://" + window.location.hostname + URL_PATH + "?q=refresh";
  const UPDATE_URL = "https://" + window.location.hostname + URL_PATH + "?q=update";
  const THROTTLE_TIME = 1000 * 60; 
  const MAX_VIEW_TIME = 1000 * 60 * 30;
  const DOC_TITLE = "View Resume - 夜空思间登录";

  let throttle = false;
  let refresh_resp = false;


  onload=init;

  function init()
  {

    setTimeout( 
      ()=> {
        refresh(REFRESH_URL);
      }, 
      REFRESH_TIME
    );

    setTimeout(
      ()=>{
        window.location.replace(MAIN_URL);
        verifyDocumentClear();
      },
      MAX_VIEW_TIME
    );

    document.onmousemove = detectMouseActivity;
    window.addEventListener('scroll', detectScrollActivity);
      
  }


  function detectMouseActivity(event)
  {
    if(debug)
    {
      console.log("Mouse movement event detected ! " + event.clientX + " : " + event.clientY);
    }
    
    updateActivity();

  }


  function detectScrollActivity(event)
  {
    if(debug)
    {
       console.log("Scrolling detected ! ");
    }
    updateActivity();
  }


  /* Clears the document if the title is unexpected */
  function clearDocument()
  {

    if(document.title !== DOC_TITLE)
    {
      document.open();
      document.write("Network disconnection application timeout.");
      document.close();
    }

  }

  /* Verfies that the sensitive document is no longer displayed */
  function verifyDocumentClear()
  {
      setTimeout(clearDocument, 5000);
  }



  function updateActivity()
  {
      if(throttle) return;

      throttle = true; 

      setTimeout(
        ()=>{
          throttle = false;
        },
        THROTTLE_TIME
      );

      refresh(UPDATE_URL);
  }


  function handleRefreshResponse(xhr)
  {
    if(xhr.readyState === XMLHttpRequest.DONE)
    {
        refresh_resp = true;
        if (xhr.status === 200 || xhr.status === 202) 
        {
            if(debug)
            {
              console.log("valid response code");
            }

            if(xhr.responseText !== 'ok')
            {
                if(debug)
                {
                  console.log("Invalid response text redirecting");
                }
                window.location.replace(MAIN_URL);
                verifyDocumentClear();
            }
          
        } 
        else
        { // Any other response code
            window.location.replace(MAIN_URL);
            verifyDocumentClear();   
        }

    }
  }


  function refresh(url)
  {
    
    if(url === REFRESH_URL)
    {
        refresh_resp = false;

        if(debug)
        {
           console.log("Refreshing page");
        }

        /* Verify that we got a response back after 5 seconds, otherwise clear doc */
        setTimeout(
          () => {

            if(!refresh_resp)
            {
              clearDocument();
            }

          },
          5000
        );

        setTimeout( 
          ()=> {
            refresh(REFRESH_URL);
          }, 
          REFRESH_TIME
        );
    }
    else
    {
        if(debug)
        {
          console.log("Updating activity");
        }
        
    }
    
    xhr = new XMLHttpRequest();

    if (!xhr) 
    {
      if(debug)
      {
         console.log("Cannot create ajax objext falling back to location method");
      }
      
      window.location.replace(MAIN_URL);
    }

    xhr.onreadystatechange = () => { handleRefreshResponse(xhr) };
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send();     

  }


})();

