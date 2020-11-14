/* 
Simple typescript to do combination/permutation generation 
or permutation using recursion. 
The script file can be run using Deno. 

deno run combination-permutation-recursive.ts

Combination and permutation are pretty much similar. 
In Combination, for a set of items , a number of positions or places
are available. It is the number of ways that these places can be filled. 

Eg. {1,2} with a single position available.  So it can be either 1 or 2. 


In Permuation, the number of positions or places is equal to the set
of items. It is the number of ways that these items can be arranged. 

Eg. {1,2} , so number of arrangements {2,1}, {1,2}

A recursive function can be used to solve for both. 

Ng Chiang Lin
14 Nov 2020
*/


/* Array of 10 numbers */
let arr: number[]  = [0,1,2,3,4,5,6,7,8,9];

/* Define an ArrayList to store all the combinations of arrays */
class ArrayList {
    combination: number[] = [];
    next: (ArrayList | null) = null;

    constructor(num?: number) 
    {
        if(num !== undefined)
        {
            this.combination.push(num);
        }
       
    }
} 

/* Helper function to add to end of another ArrayList */
function addItem(dest: (ArrayList|null), new_list_item: (ArrayList|null)):(ArrayList|null)
{
    if(new_list_item === null)
    {
        return null;
    }

    /* dest is empty */
    if(dest === null)
    {
        dest = new_list_item;
        return dest;
    }

    /* append to end of dest */
    let tmp = dest;
    while(tmp.next)
    {
        tmp = tmp.next; 
    }

    tmp.next = new_list_item;
    return dest;
}



/* 
   Recursive function to process each position 
   Takes a positive number that indicates the positions available.
   Takes an array of digits as total number of items to generate combination.
   Returns the an ArrayList that contains all possible combinations or permutations

   When the number of positions is equal to the number of items. It is a permutation. 

*/
function createSubCombin(position:number, digits:number[]):(ArrayList|null) {
    
    /* check for error inputs */
    if(digits.length === 0 || position <=0 || position > digits.length)
    {
        return null;
    }

    let ret:(ArrayList|null) = null;

    /* last position, condition to end recursive */
    if(position === 1)
    { 
        for(let i=0;i<digits.length;i++)
        {
            ret = addItem(ret, new ArrayList(digits[i]));
            
        }
       
        return ret;
    }    


   
   for(let i=0 ; i<digits.length; i++)
   {

        let current_digit = digits[i]; 
        let remain_digits:number[];
        if(i===0) { 
            remain_digits = digits.slice(i + 1);
        }
        else
        {
            remain_digits = (digits.slice(i+1)).concat(digits.slice(0, i));
        }
        
        let subresult = createSubCombin(position -1, remain_digits);

        let tmp = subresult;
        while(tmp != null)
        {
            tmp.combination.unshift(current_digit);
            tmp = tmp.next;
        }

        ret=addItem(ret, subresult);

   }

   return ret;
  
}



/* Helper function prints the listing of combinations */
function printlist(list:(ArrayList|null))
{
    while(list)
    {
        console.log(list.combination);
        list = list.next;
    }
}

/* To get 3 number combinations out of an array of 10 elements */
/* Consider the 3 numbers as position A, B, C represented by 3,2,1 respectively */
let combination_position = 3;
let list = createSubCombin(combination_position, arr);
console.log("Number of possible combinations for", combination_position, "out of", arr.length);

if(list !== null)
{
    let tmp:(ArrayList|null) = list;
    let num_combination = 0;
    while(tmp !== null)
    {
        num_combination++;
        tmp = tmp.next; 
    }
    console.log(num_combination);
    printlist(list);
}
else
{
    console.error("An error occurred while trying to generate combinations");
}



