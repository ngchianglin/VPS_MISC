/* 
Simple typescript to do permutation generation 
using recursion. 
The script file can be run using Deno. 

deno run permutation-recursive.ts

Permutation is the number of ways that things can be arranged.
The ordering matters in permutation.

Eg. {1,2} , the number of arrangements is {2,1}, {1,2}

For an array of integers, what are the possible permutations for n items out of this array. 
A recursive function can be used to solve for this. 

Ng Chiang Lin
26 Nov 2020
*/


/* Array of 10 numbers */
let arr: number[]  = [0,1,2,3,4,5,6,7,8,9];

/* Define an ArrayList to store all the permutations */
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
   Takes an array of digits as total number of items to generate permutations.
   Returns the an ArrayList that contains all possible permutations

*/
function createPermutation(position:number, digits:number[]):(ArrayList|null) {
    
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
        
        let subresult = createPermutation(position -1, remain_digits);

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



/* Helper function prints the listing of permutations */
function printlist(list:(ArrayList|null))
{
    while(list)
    {
        console.log(list.combination);
        list = list.next;
    }
}

/* To get 3 number permutations out of an array of 10 elements */
/* Consider the 3 numbers as position A, B, C represented by 3,2,1 respectively */
let position = 3;
let list = createPermutation(position, arr);
console.log("Number of possible permutations for", position, "out of", arr.length);

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



